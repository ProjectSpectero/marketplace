<?php

use Illuminate\Database\Seeder;

class NodesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Node::class, 100)->create();
        factory(App\NodeGroup::class, 50)->create();
        factory(App\NodeIPAddress::class, 1000)->create();

        $systemTemplates = [
            '{"CPU":{"Model":"Intel(R) Core(TM) i7-3770K CPU @ 3.50GHz","Cores":4,"Threads":8,"Cache Size":1024},"Memory":{"Physical":{"Used":16171319296,"Free":9536708608,"Total":25708027904},"Virtual":{"Used":27597258752,"Free":6432268288,"Total":34029527040}},"Environment":{"Hostname":"BLEU","OS Version":{"platform":2,"servicePack":"","version":{"major":6,"minor":2,"build":9200,"revision":0,"majorRevision":0,"minorRevision":0},"versionString":"Microsoft Windows NT 6.2.9200.0"},"64-Bits":true}}',
            '{"CPU":{"Model":" Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":8,"Cache Size":" 8192 KB"},"Memory":{"Physical":{"Used":771387392,"Free":1376096256,"Total":2147483648}},"Environment":{"Hostname":"dev","OS Version":{"platform":4,"servicePack":"","version":{"major":2,"minor":6,"build":32,"revision":42,"majorRevision":0,"minorRevision":42},"versionString":"Unix 2.6.32.42"},"64-Bits":true}}'
        ];

        $system_data = array_map(function ($data) { return json_decode($data, true); }, $systemTemplates);

        foreach (\App\Node::all() as $node)
        {
            $this->createServices($node);
            $node->system_data = array_random($system_data);
            $node->saveOrFail();
        }

        foreach (\App\Node::all()->random(40) as $node)
        {
            $node->group_id = null;
            $node->save();
        }

        $proGroup = \App\NodeGroup::find(env('PRO_PLAN_GROUP_ID'));
        $proGroup->friendly_name = 'Spectero Pro';
        $proGroup->plan = \App\Constants\SubscriptionPlan::PRO;
        $proGroup->market_model = \App\Constants\NodeMarketModel::LISTED_SHARED;
        $proGroup->price = 9.99;
        $proGroup->saveOrFail();

        foreach (\App\Node::all()->random(45) as $node)
        {
            $node->market_model = \App\Constants\NodeMarketModel::LISTED_SHARED;
            $node->group_id = $proGroup->id;
            $node->save();
        }

        foreach (\App\NodeGroup::all() as $group)
        {
            foreach ($group->nodes as $node)
            {
                if ($group->user_id != $node->user_id)
                {
                    $node->user_id = $group->user_id;
                    $node->saveOrFail();
                }
            }
        }

        // Let's add the only real nodes
        $this->seedRealNodes();
    }

    private function seedRealNodes ()
    {
        /* Required Daemon Side SQL
            UPDATE `Configuration` SET `Value`='True' WHERE `Key`='cloud.connect.status';
            UPDATE `Configuration` SET `Value`='23d0a0c4-dc91-4960-b6fc-2d874fb9f50f' WHERE `Key`='sys.id';
            UPDATE `Configuration` SET `Value`='b96rU_Y2-5gj5r1Kfx40M51Qz-_1ps_PH19_--__E_9-_7vH' WHERE `Key`='crypto.jwt.key';
            UPDATE `User` SET `Password`='$2a$10$6Etg74BdeweUXOl6/RlyuOsb8q6Lm2RhqwRD9IA/T77ErdMdnd85m' WHERE `AuthKey`='spectero';
            INSERT INTO `Configuration`(`Key`,`Value`,`CreatedDate`,`UpdatedDate`) VALUES ('cloud.connect.id','101','2018-07-19 23:33:01.4711129','2018-07-19 23:33:01.4711129');
            INSERT INTO `Configuration`(`Key`,`Value`,`CreatedDate`,`UpdatedDate`) VALUES ('cloud.connect.node-key','25e7e751047aad89f9fd7fa19fe806618ee9e944cbeb861398f8e4534498659a','2018-07-19 23:33:01.4711129','2018-07-19 23:33:01.4711129');
        */

        $realNode = new \App\Node();
        $realNode->id = 101;
        $realNode->ip = '23.158.64.30';
        $realNode->port = 6024;
        $realNode->friendly_name = 'Real Test Node 1';
        $realNode->protocol = 'http';
        $realNode->access_token = 'spectero:_Bv80f4--_oTG-_N';
        $realNode->install_id = '23d0a0c4-dc91-4960-b6fc-2d874fb9f50f';
        $realNode->status = \App\Constants\NodeStatus::CONFIRMED;
        $realNode->market_model = \App\Constants\NodeMarketModel::LISTED_SHARED;
        $realNode->user_id = 6;
        $realNode->price = 15.99;
        $realNode->asn = 133535;
        $realNode->city = 'Seattle';
        $realNode->cc = 'US';
        $realNode->version = \App\Constants\DaemonVersion::ZERO_ONE_ALPHA;
        $realNode->system_data = json_decode('{"CPU":{"Model":"Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":40,"Cache Size":"8192 KB"},"Memory":{"Physical":{"Used":222289920,"Free":851451904,"Total":1073741824}},"Environment":{"Hostname":"daemon-test-0","OS Version":{"Platform":4,"ServicePack":"","Version":{"Major":2,"Minor":6,"Build":32,"Revision":42,"MajorRevision":0,"MinorRevision":42},"VersionString":"Unix 2.6.32.42"},"64-Bits":true}}', true);
        $realNode->app_settings = json_decode('{
			"BlockedRedirectUri": "https://blocked.spectero.com/?reason={0}&uri={1}&data={2}",
			"DatabaseFile": "Database/db.sqlite",
			"AuthCacheMinutes": 5.0,
			"LocalSubnetBanEnabled": true,
			"Defaults": null,
			"PasswordCostLowerThreshold": 10,
			"JWTTokenExpiryInMinutes": 60,
			"JWTRefreshTokenDelta": 30,
			"PasswordCostCalculationIterations": 10,
			"PasswordCostCalculationTestTarget": "srU/Lz4RYsz}U[D-e-5Tv+>\'$TwT=\'dvV?68",
			"PasswordCostTimeThreshold": 100.0,
			"RespectEndpointToOutgoingMapping": true,
			"BindToUnbound": true,
			"LoggingConfig": "nlog.config",
			"DefaultOutgoingIPResolver": "https://api.ipify.org/",
			"InMemoryAuth": true,
			"InMemoryAuthCacheMinutes": 1,
			"AutoStartServices": true,
			"LogCommonProxyEngineErrors": false,
			"IgnoreRFC1918": true,
			"HaltStartupIfServiceInitFails": true,
			"JobsConnectionString": "Data Source=Database/jobs.sqlite;"
		}', true);
        $realNode->system_config = json_decode('[
			{
				"key": "sys.id",
				"value": "23d0a0c4-dc91-4960-b6fc-2d874fb9f50f",
				"id": 1,
				"createdDate": "2018-07-10T06:54:24.6464592",
				"updatedDate": "2018-07-10T06:54:24.6464592"
			},
			{
				"key": "cloud.connect.status",
				"value": "False",
				"id": 2,
				"createdDate": "2018-07-10T06:54:24.6702596",
				"updatedDate": "2018-07-10T06:54:24.6702596"
			},
			{
				"key": "http.config",
				"value": "{\"listeners\":[{\"Item1\":\"23.158.64.30\",\"Item2\":10240},{\"Item1\":\"23.158.64.31\",\"Item2\":10240},{\"Item1\":\"23.158.64.32\",\"Item2\":10240},{\"Item1\":\"23.158.64.33\",\"Item2\":10240},{\"Item1\":\"23.158.64.34\",\"Item2\":10240},{\"Item1\":\"23.158.64.35\",\"Item2\":10240},{\"Item1\":\"23.158.64.36\",\"Item2\":10240},{\"Item1\":\"23.158.64.37\",\"Item2\":10240},{\"Item1\":\"23.158.64.38\",\"Item2\":10240},{\"Item1\":\"23.158.64.39\",\"Item2\":10240},{\"Item1\":\"23.158.64.40\",\"Item2\":10240},{\"Item1\":\"23.158.64.41\",\"Item2\":10240},{\"Item1\":\"23.158.64.42\",\"Item2\":10240},{\"Item1\":\"23.158.64.43\",\"Item2\":10240}],\"allowedDomains\":null,\"bannedDomains\":null,\"proxyMode\":\"Normal\"}",
				"id": 3,
				"createdDate": "2018-07-10T06:54:24.6827009",
				"updatedDate": "2018-07-10T06:54:24.6827009"
			},
			{
				"key": "auth.password.cost",
				"value": "10",
				"id": 4,
				"createdDate": "2018-07-10T06:54:37.5479749",
				"updatedDate": "2018-07-10T06:54:37.5479749"
			},
			{
				"key": "crypto.jwt.key",
				"value": "b96rU_Y2-5gj5r1Kfx40M51Qz-_1ps_PH19_--__E_9-_7vH",
				"id": 5,
				"createdDate": "2018-07-10T06:54:37.5581235",
				"updatedDate": "2018-07-10T06:54:37.5581235"
			},
			{
				"key": "crypto.ca.password",
				"value": "8_31AhKHPL0CXMPMg4u41R-37-07-8-W1_5_wFHejs9r1KnE",
				"id": 6,
				"createdDate": "2018-07-10T06:54:40.0057876",
				"updatedDate": "2018-07-10T06:54:40.0057876"
			},
			{
				"key": "crypto.server.password",
				"value": "_CeA__KEE4Pk-pFmUoX4_yg_O_X_22NnYa15-_k_OBB9-_bE",
				"id": 7,
				"createdDate": "2018-07-10T06:54:40.0124663",
				"updatedDate": "2018-07-10T06:54:40.0124663"
			},
			{
				"key": "crypto.ca.blob",
				"value": "MIIKSQIBAzCCCg8GCSqGSIb3DQEHAaCCCgAEggn8MIIJ+DCCBK8GCSqGSIb3DQEHBqCCBKAwggScAgEAMIIElQYJKoZIhvcNAQcBMBwGCiqGSIb3DQEMAQYwDgQI8oH9XGcSp6cCAggAgIIEaM7J8GQyqiv/svA/AOuIIcPAvrVmSJKjvFWpEAenb9sAb5MDvOK1RX/zH4iHkPv+gIvTusUNlX2K8DEtkmbFuehRmnIZ5IsMeT58Y8iN/UPeR6ZCTxXiSdDbo+RQGq+badI5DztT+KnrQ4z/kAyxxPS+rGhXW0Ffsw+zV2b1bPXG6i2JYJKiY942qJFnE9tB0ullX42RGZXZJ0zFHOPxqor4I2Olpp8fYpL5FlhkpwGz5q+fhEKUPKkSdWcfFhS/NP/NuRHPelQoqLt3mkZgjM33WgOV0cQKppT4Gjb8vwOtJdlVkL0t19GVLT4z/fFT3QXa5oqrF5Tkk2P7Nw7BnTer4n34eoIYPrJWdLp3ByZPBK579VSQibwE5H1tpIh7JpF3/Pxz3S7wL/jte3bF4TJ+hXotThYQhvpI4rHdFEEWQu7kQxijXiQdmXRIQu5TZbDP2NB5aFSeSV0LoBaTDSQ5NsyYHSIwTwif63IWfgYsm46DRDFBEa/gQmObSfI/0s4XNCwcDcChVUEpGUlLr1MtBT4eSzMcfrga+c0s813GgQbhVgI9IQpTmYYRmJM7itmk/m9VYlgYoJMrAfcpgUwwz8MM8sVvb0VMHFnBM3sVAIp8uUGs4FKoz0DDeWX6G3VsAr9+ad7WSbhVS//fyaVuXINA37vmLU5jFPidd8R/XOPGLXc58RS1JipVEdv19VezRHorG1NX8d83HtioOpR9GKWzmnKMz4BeS4ACrMNqIvBvORnnv5mLJ7gYR3KI8unsscZtmjehubpc6k4xOWs/DaCJfqM5oUFs1+vtSB7QObaA1BYj67rckxhmHqUn7ev2GSIvb5Iq1FJCR1gaCU88PXvvWZwqF0vrEGs7ytEAAqn1sC7tlgR178eWnyWmWQWcXoutoILlzmH4olPjnGQe/Gduf9IW/pKNBnzxtfI9ieiZo7cd2szrj/SqCRUfQg6rJcsOHOebPdALpoJSJQGiC5ToUiLs8Q+FhAQ5E9FEaykORWtsc4c9E4iZ7q+rFU3DPRq5PQLwvn/NPfAZhF14uEJgQQqGt/hT6vf+BrpeSxmvsMMiFQi/g7jL6YBICsut1v+3NwiMO+u/2pWcXAtxCYm6uyyslIEBi/FsL9ZdidKkfovyyNGwbTVddcBSp9XQxwshGREVpjb3uBCKK25MgqdplW7I5deoQrCT47txGwdI/GWueHDHOTw2JJP1013dF9oiylptzMJUVXFBIGDpFHNfEZBLk1+2A/3YbMTOXIOmdC0FbnzsES9UU0ZTJJxG0rkhfelAwHcukWrhkT+1NMW0hGFi2kFDTS9eAyB7oy4IWQzFYS/bbsL0BuTRuoAz1y/lClL+9JKhfEZFvmIPkoe7CAgD92unXffXwMwTknd4P5OUKIhvPEeCw57whv3+Hwf2pTljmgc304EyoGGypYrnLIpxy775xDrp1EI94qyXV353rl+ADfXOuRC4kkWP+G+wqNFyU6cFLoC5CI0+iiFTBhL8GjCCBUEGCSqGSIb3DQEHAaCCBTIEggUuMIIFKjCCBSYGCyqGSIb3DQEMCgECoIIE7jCCBOowHAYKKoZIhvcNAQwBAzAOBAh77an+kMyNewICCAAEggTIKbsXjUZCrPqVSLBkiop/2zausMcnU+1iDVm/KvZg01Yr/LC8HqzvmfVV3/JJwA4zlzHo9QDTKLCRf9j0D5d+/X0lm9xf/ZrWlK3Z9utN986sCQXZG0HnYuMcO7OzJguW3aWO1LJwjHdzQDkyf+C0QehEWD+GTg86Qqza3cHNXYorlKCQu2qrrdIY6lWmhjsR79qN8ncWh8GKU0xEUXkA4OkeAn9m1Z+zWc3V4hAjh4tAXWADWmC0oVKo+P3CqtPC6mGpmockh7GdBTGnNtn8k9hH3xV8IKLy+C7Z8YC4j4wUyn880Kwxqs8FsrNGUk8xcNGpfB4ddmRL8SzgNmmfQm+aFzEvjhzFaknROzQ4iH/6HyR/S4MWCPQwmUGzN7UVktlPw2bfxyZMjoGtzhbRCGUPTL/LqXY2nGu1Fsj4NhOT0rKRInH0CREaa6wyquIDktOrN/JgKoUzP4k5Tkob5Apc7pnz3QICeEoX2mtbq2M2lveyzfiVppGbANSwoiYw0Ofo7/igFGtBtT2Rccii1a/k6xOkm5p3FH3QpCUTiHAAZ/Caiux6SWRnHYCWjx4SuMjICYTkcwJqiIBAKc/7+uUyXGGrKXXQbDOYaNeatFR4MjjI2paHUzMMMgXfkQSn1ZMlpwziMFMhJ1ft7mgKidIB3gnl8/MzjZE5Ua+WG24voxhlpNdbBFG1xNsPjSMtxwl1IrnYsBeY+9c/4OWQmyNLyAldPy1S1jgElw+grfVyEHWTiMJtZa8jbQzIeYc33CQiI3SEQCbrDdZRwg4yOpM8faG/fmWP4lAtpgU1mObxr0oeEdCndoQUY98AmYz9J2Tmuzf7cjqkC/cvJDCMr+/zx+du9Gnn5tBjhk+6CQdnVL/7tXyt1LDsgYE/IrFil4KxNUocAQtGGmzzjiw9/Y4be7zRGI53ZA/PAngnby7h3yVsverH0iKtl8FvMClWGBzQ+YkXuN1IfUQPByrJEPNImtTb0Uwk6m+MyyytSw6poKYCRPUuIS/22QB1d3ne49d3sWdNQNve3VW4UHR9AH+imrRebuMVlXttNJvtxbE7yynkEEKRyI4ezMzvwROxx/AeSnMPODVFQCakWLlo/B8v0WRqz3NZ14gv+ogSBlcCS2sUC569sylNMCmLmWlxk/TRSI8EqPbmmpnk3xU9jYnFOn618R/y1NzbAZJwONp456h9e+3Og3JzAO2n2pWeRpQRl0EMC5rCdUwNLv6jGGwAh5z4LVQu4nRKsJ6qzNViyHLWvyFnx5ONUb1ukruFZZcEt1JL32sRGZ8MTJ8BiMNA5cGuNAeZDpChvgA97ijM2e6/Ybqa3BwDZV69BkDelmb/dBSS23Q4pjdJzmbPlfPZad/GKWhpl1FibcgKJlV6cZJZkXK62TQknXW0M2SWd61sL8zhdip3XfTOVH5d+fzMHpRHxcVgpRPoDobi+KSnvm/kKDWYSyrDp/HSQwqG+s6WCCeddSWTvowq7V4RW+XQzyycoWKNKMZsIqpkOLqBzZt7yWGj/h5fceFf5UVuEo8cd2QloanfzI6/7J6PYbpIUcYpKkACp4bw8Ni6/XgqwZ8OUjVQcIIiWE2q2Tpwuzx8bu2gEE5nvDYexxPVS+QhcpddGUZzMSUwIwYJKoZIhvcNAQkVMRYEFNt7G/M5RwfiH140T51QF49jsYwJMDEwITAJBgUrDgMCGgUABBRyXitFofQ1ytmq23CLG2DtxfNjQwQIPpiLkrwmXJMCAggA",
				"id": 8,
				"createdDate": "2018-07-10T06:54:40.0189212",
				"updatedDate": "2018-07-10T06:54:40.0189212"
			},
			{
				"key": "crypto.server.blob",
				"value": "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",
				"id": 9,
				"createdDate": "2018-07-10T06:54:40.0235747",
				"updatedDate": "2018-07-10T06:54:40.0235747"
			},
			{
				"key": "crypto.server.chain",
				"value": "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",
				"id": 10,
				"createdDate": "2018-07-10T06:54:40.0301352",
				"updatedDate": "2018-07-10T06:54:40.0301352"
			},
			{
				"key": "vpn.openvpn.config.listeners",
				"value": "[{\"IPAddress\":\"0.0.0.0\",\"Port\":1194,\"ManagementPort\":35100,\"Protocol\":\"TCP\",\"Network\":\"172.16.224.0/24\"},{\"IPAddress\":\"0.0.0.0\",\"Port\":1194,\"ManagementPort\":35101,\"Protocol\":\"UDP\",\"Network\":\"172.16.225.0/24\"}]",
				"id": 11,
				"createdDate": "2018-07-10T06:54:40.0379566",
				"updatedDate": "2018-07-10T06:54:40.0379566"
			},
			{
				"key": "vpn.openvpn.config.template",
				"value": "{\"Listener\":null,\"AllowMultipleConnectionsFromSameClient\":false,\"ClientToClient\":false,\"DhcpOptions\":[],\"MaxClients\":1024,\"PushedNetworks\":[],\"RedirectGateway\":[\"Def1\"]}",
				"id": 12,
				"createdDate": "2018-07-10T06:54:40.0485107",
				"updatedDate": "2018-07-10T06:54:40.0485107"
			}
		]', true);

        $realNode->save();

        $this->createServices($realNode);
    }

    private function createServices(\App\Node $node)
    {
        foreach (\App\Constants\ServiceType::getConstants() as $type)
        {
            // Let's not always create 4 services.
            if (mt_rand(1, 10) % 2 == 0)
                break;

            $service = new \App\Service();
            $service->node_id = $node->id;
            $service->type = $type;
            $service->config = [
                                   'DatabaseFile' => 'Database/db.sqlite',
                                   "PasswordCostTimeThreshold" => 100.0,
                                   "SpaCacheTime" => 1,
                               ];

            $randStr = null;
            $ref = null;
            if ($type != \App\Constants\ServiceType::HTTPProxy)
            {
                $ref = $this->generateAccessReferences(2);
                $randStr = \App\Libraries\Utility::getRandomString(2);
                for ($i = 0; $i <= 5; $i++)
                    $randStr .= PHP_EOL . $randStr;
            }
            else
                $ref = $this->generateAccessReferences(mt_rand(5, 10));

            $service->connection_resource = [
                                                'accessReference' => [
                                                    $ref
                                                ],
                                                'accessConfig' => $randStr,
                                                'accessCredentials' => array_random(['SPECTERO_USERNAME_PASSWORD', $node->access_token])
                                            ];

            $service->saveOrFail();
        }
    }

    private function generateAccessReferences(int $bound = 5) : array
    {
        $out = [];

        while ($bound)
        {
            $ipSeed = mt_rand(1, 63);
            $ip = $ipSeed . '.' . $ipSeed * 2 . '.' . $ipSeed * 3 . '.' . $ipSeed * 4 . ':' . mt_rand(10240, 65534);

            $out[] = $ip;

            $bound--;
        }

        return $out;
    }
}
