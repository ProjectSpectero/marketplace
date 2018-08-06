<?php

$factory->define(App\Node::class, function (Faker\Generator $faker) {
    return [
        'ip' => $faker->ipv4,
        'friendly_name' => $faker->colorName,
        'port' => $faker->randomNumber(4),
        'protocol' => 'http',
        'access_token' => 'cloud:thisIsAPasswordButYouDontSeeIt',
        'install_id' => $faker->sha256,
        'status' => array_random(\App\Constants\NodeStatus::getConstants()),
        'user_id' => $faker->numberBetween(6, 10),
        'price' => $faker->numberBetween(5, 100),
        'market_model' => array_random(\App\Constants\NodeMarketModel::getConstants()),
        'group_id' => array_random([ null, $faker->numberBetween(1, 100) ]),
        'version' => array_random(\App\Constants\DaemonVersion::getConstants()),
        'asn' => $faker->numberBetween(1, 65534),
        'city' => $faker->city,
        'cc' => $faker->countryCode,
        'app_settings' => json_decode('{
			"BlockedRedirectUri": "https://blocked.spectero.com/?reason={0}&uri={1}&data={2}",
			"DatabaseFile": "Database/db.sqlite",
			"AuthCacheMinutes": 5.0,
			"LocalSubnetBanEnabled": true,
			"Defaults": null,
			"PasswordCostLowerThreshold": 10,
			"JWTTokenExpiryInMinutes": 60,
			"JWTRefreshTokenDelta": 30,
			"PasswordCostCalculationIterations": 50,
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
		}', true),
        'system_config' => json_decode('[
			{
				"key": "sys.id",
				"value": "23d0a0c4-dc91-4960-b6fc-2d874fb9f50f",
				"id": 1,
				"createdDate": "2018-07-03T07:55:22.0207238",
				"updatedDate": "2018-07-03T07:55:22.0207238"
			},
			{
				"key": "cloud.connect.status",
				"value": "False",
				"id": 2,
				"createdDate": "2018-07-03T07:55:22.0412282",
				"updatedDate": "2018-07-03T07:55:22.0412282"
			},
			{
				"key": "http.config",
				"value": "{\"listeners\":[{\"Item1\":\"23.158.64.31\",\"Item2\":10240},{\"Item1\":\"23.158.64.32\",\"Item2\":10240},{\"Item1\":\"23.158.64.33\",\"Item2\":10240},{\"Item1\":\"23.158.64.34\",\"Item2\":10240},{\"Item1\":\"23.158.64.35\",\"Item2\":10240},{\"Item1\":\"23.158.64.36\",\"Item2\":10240},{\"Item1\":\"23.158.64.37\",\"Item2\":10240},{\"Item1\":\"23.158.64.38\",\"Item2\":10240},{\"Item1\":\"23.158.64.39\",\"Item2\":10240},{\"Item1\":\"23.158.64.40\",\"Item2\":10240}],\"allowedDomains\":null,\"bannedDomains\":null,\"proxyMode\":\"Normal\"}",
				"id": 3,
				"createdDate": "2018-07-03T07:55:22.0562613",
				"updatedDate": "2018-07-03T16:03:39.5297055"
			},
			{
				"key": "auth.password.cost",
				"value": "10",
				"id": 4,
				"createdDate": "2018-07-03T07:55:35.0842417",
				"updatedDate": "2018-07-03T07:55:35.0842417"
			},
			{
				"key": "crypto.jwt.key",
				"value": "j7Zh__T7G__3Wkmi__845cH_Kb7k151-_MRY-0_x6dP_Fp0Q",
				"id": 5,
				"createdDate": "2018-07-03T07:55:35.0956109",
				"updatedDate": "2018-07-03T07:55:35.0956109"
			},
			{
				"key": "crypto.ca.password",
				"value": "_OX-95VJ6yjR6x2-HfE-DX_1De_7ABq-D7f__8_m3_S_5b0G",
				"id": 6,
				"createdDate": "2018-07-03T07:55:37.1222999",
				"updatedDate": "2018-07-03T07:55:37.1222999"
			},
			{
				"key": "crypto.server.password",
				"value": "J_4-95ma_V5_E_8QVXW44_5f7tR__50Hn867uhvC6NDh2bgN",
				"id": 7,
				"createdDate": "2018-07-03T07:55:37.1286189",
				"updatedDate": "2018-07-03T07:55:37.1286189"
			},
			{
				"key": "crypto.ca.blob",
				"value": "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",
				"id": 8,
				"createdDate": "2018-07-03T07:55:37.1349659",
				"updatedDate": "2018-07-03T07:55:37.1349659"
			},
			{
				"key": "crypto.server.blob",
				"value": "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",
				"id": 9,
				"createdDate": "2018-07-03T07:55:37.1393699",
				"updatedDate": "2018-07-03T07:55:37.1393699"
			},
			{
				"key": "crypto.server.chain",
				"value": "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",
				"id": 10,
				"createdDate": "2018-07-03T07:55:37.146132",
				"updatedDate": "2018-07-03T07:55:37.146132"
			},
			{
				"key": "vpn.openvpn.config.listeners",
				"value": "[{\"IPAddress\":\"0.0.0.0\",\"Port\":1194,\"ManagementPort\":35100,\"Protocol\":\"TCP\",\"Network\":\"172.16.224.0/24\"},{\"IPAddress\":\"0.0.0.0\",\"Port\":1194,\"ManagementPort\":35101,\"Protocol\":\"UDP\",\"Network\":\"172.16.225.0/24\"}]",
				"id": 11,
				"createdDate": "2018-07-03T07:55:37.1535309",
				"updatedDate": "2018-07-03T07:55:37.1535309"
			},
			{
				"key": "vpn.openvpn.config.template",
				"value": "{\"Listener\":null,\"AllowMultipleConnectionsFromSameClient\":false,\"ClientToClient\":false,\"DhcpOptions\":[],\"MaxClients\":1024,\"PushedNetworks\":[],\"RedirectGateway\":[\"Def1\"]}",
				"id": 12,
				"createdDate": "2018-07-03T07:55:37.1638349",
				"updatedDate": "2018-07-03T07:55:37.1638349"
			}
		]', true)
    ];
});
