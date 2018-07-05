# Automatically generated OpenVPN client config file by Spectero Cloud
# Generated on {{ \Carbon\Carbon::now('UTC') }} (UTC)
# Note: this config file contains inline private keys
# and therefore should be kept confidential!
# Note: this configuration is user-locked to the username below
# SPECTERO_USERNAME={{ $username }}
# SPECTERO_INSTANCE_ID={{ $systemId }}

setenv FORWARD_COMPATIBLE 1
client
server-poll-timeout 4
nobind

@foreach ($listeners as $listener)
remote {{ $listener['ip'] }} {{ $listener['port'] }} {{ $listener['protocol'] }}
@endforeach

dev tun
dev-type tun
reneg-sec 604800
sndbuf 100000
rcvbuf 100000
max-routes 2048

comp-lzo
verb 3
setenv PUSH_PEER_INFO
auth-user-pass

<pkcs12>
@foreach($certChunks as $certChunk)
{!! $certChunk !!}
@endforeach
</pkcs12>

# key-direction 1
# <tls-auth>
#
#
# </tls-auth>

# Extra user-defined configuration
cipher {{ $cipherType }}