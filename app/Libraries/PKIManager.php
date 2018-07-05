<?php

namespace App\Libraries;


use App\Errors\FatalException;
use App\Models\Opaque\CAInfo;
use Symfony\Component\Process\Process;

class PKIManager
{
    const extractKeyFromPKCS12Template = 'openssl pkcs12 -in %s -nocerts -out %s -passin pass:%s -nodes';
    const extractCertFromPKCS12Template = 'openssl pkcs12 -in %s -clcerts -nokeys -out %s -passin pass:%s';

    const generateUserPrivateKeyAndCSRTemplate = 'openssl req -nodes -newkey rsa:2048 -keyout %s -out %s -subj "%s"';

    const generateFullPKCS12UserChainTemplate = 'openssl pkcs12 -export -in %s -inkey %s -certfile %s -out %s -passout pass:%s';

    const issueSignedUserCertTemplate = 'openssl x509 -req -in %s -CA %s -CAkey %s -CAcreateserial -out %s -days %d -sha256';

    const extratedCAPKCS12FileName = 'ca.p12';
    const extractedCACertFileName = 'ca.crt';
    const extractedCACertKeyFileName = 'ca.key';

    const createdUserCSRFileName = 'user.csr';
    const createdUserCertFileName = 'user.crt';
    const createdUserCertKeyFileName = 'user.key';

    const finalizedPKCS12UserFullChainFileName = 'user.p12';

    const generatedCASerialFileName = 'ca.srl';

    public static function issueUserChain (CAInfo $caInfo, string $userIdentifier, string $exportPassword = '') : string
    {
        $seed = Utility::getRandomString();
        $confirmedWorkingDir = self::createTemporaryDirectory($seed);


        $caBlob = $caInfo->blob;
        $caPassword = $caInfo->password;

        // First, we decode and write the CA blob to disk so it can be processed by OpenSSL.
        $decodedBlob = base64_decode($caBlob, true);

        if ($decodedBlob === false)
            throw new FatalException("Provided caBlob was NOT a valid base64 encoded PKCS12 file!");

        $certExpiryDays = env('PKI_CERT_EXPIRY_IN_DAYS', 1825);

        $caBlobPath = Utility::joinPaths($confirmedWorkingDir, self::extratedCAPKCS12FileName);
        file_put_contents($caBlobPath, $decodedBlob);

        // Now, let's process the cert+key out of it.
        $keyExtractionCommand = sprintf(self::extractKeyFromPKCS12Template, self::extratedCAPKCS12FileName, self::extractedCACertKeyFileName, $caPassword);
        self::runAndEnsureSuccess($keyExtractionCommand, $confirmedWorkingDir);

        $certExtractionCommand = sprintf(self::extractCertFromPKCS12Template, self::extratedCAPKCS12FileName, self::extractedCACertFileName, $caPassword);
        self::runAndEnsureSuccess($certExtractionCommand, $confirmedWorkingDir);

        // Let's get the user's CSR + Private key going
        $csrAndPrivateKeyGenerationCommand = sprintf(self::generateUserPrivateKeyAndCSRTemplate, self::createdUserCertKeyFileName,
                                                     self::createdUserCSRFileName, self::generateCommonNameFromIdentifier($userIdentifier));
        self::runAndEnsureSuccess($csrAndPrivateKeyGenerationCommand, $confirmedWorkingDir);

        // Now, let's sign it with the CA cert.
        $issueCertificateCommand = sprintf(self::issueSignedUserCertTemplate, self::createdUserCSRFileName,
                                           self::extractedCACertFileName, self::extractedCACertKeyFileName,
                                            self::createdUserCertFileName, $certExpiryDays);
        self::runAndEnsureSuccess($issueCertificateCommand, $confirmedWorkingDir);

        // Let's now package it all up into a PKCS12 archive
        $finalPKCS12GenerationCommand = sprintf(self::generateFullPKCS12UserChainTemplate, self::createdUserCertFileName, self::createdUserCertKeyFileName,
            self::extractedCACertFileName, self::finalizedPKCS12UserFullChainFileName, $exportPassword);
        self::runAndEnsureSuccess($finalPKCS12GenerationCommand, $confirmedWorkingDir);

        // Alright, our chain file is now ready to go. Let's read it, and base64_encode it to prepare for storage.
        $userChainAbsolutePath = Utility::joinPaths($confirmedWorkingDir, self::finalizedPKCS12UserFullChainFileName);
        $userChainBytes = file_get_contents($userChainAbsolutePath);

        if ($userChainBytes === false)
            throw new FatalException("Despite OpenSSL command(s) succeeding, fread() failed on the generated userchain for $userIdentifier :(");

        // We're all done, let's clean up all our temp files.
        self::cleanup($confirmedWorkingDir, self::finalizedPKCS12UserFullChainFileName, self::createdUserCertFileName, self::createdUserCertKeyFileName,
                                                self::createdUserCSRFileName, self::extratedCAPKCS12FileName, self::extractedCACertFileName, self::extractedCACertKeyFileName,
                                                self::generatedCASerialFileName);

        return base64_encode($userChainBytes);
    }

    private static function cleanup ($baseDir, ...$files) : void
    {
        foreach ($files as $file)
        {
            $absolutePath = Utility::joinPaths($baseDir, $file);
            unlink($absolutePath);
        }

        rmdir($baseDir);
    }

    private static function generateCommonNameFromIdentifier (string $identifier) : string
    {
        return "/CN=$identifier";
    }

    private static function ensureOpenSSL ()
    {
        $binary = strpos(strtolower(PHP_OS), 'win') > -1 ? "where" : 'which';
        $commandToRun = "$binary openssl";

        self::runAndEnsureSuccess(new Process($commandToRun));
    }

    private static function runAndEnsureSuccess(string $command, string $workingDir = null)
    {
        $process = new Process($command);

        if (! empty($workingDir))
            $process->setWorkingDirectory($workingDir);

        $process->run();

        if (! $process->isSuccessful())
        {
            $failedCommand = $process->getCommandLine();
            throw new FatalException("Command $failedCommand did not execute successfully!");
        }
    }

    private static function createTemporaryDirectory (string $seed) : string
    {
        $base = sys_get_temp_dir();
        $actualDirName = Utility::joinPaths($base, $seed);

        // Do NOT call this function multiple times with the same seed.
        if (is_file($actualDirName) || is_dir($actualDirName))
            throw new FatalException("$actualDirName already exists, this is not supposed to happen!");

        if (! mkdir($actualDirName) && ! is_dir($actualDirName))
            throw new FatalException("$actualDirName could NOT be created :(");

        return $actualDirName;
    }

}