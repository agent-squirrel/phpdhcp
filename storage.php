<?php
/*
  Copyright 2009 Angelo R. DiNardi (angelo@dinardi.name)
 
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
 
    http://www.apache.org/licenses/LICENSE-2.0
 
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

class dhcpStorage {
    function getAttributesForClient($mac) {
	$config = 'config.ini';
	if (file_exists($config)) {
		$confarray = parse_ini_file($config);
	        return array(
	            'yiaddr' => array_map('intval', explode('.', $confarray['ipaddress'])),
	            'subnet_mask' => array_map('intval', explode('.', $confarray['subnetmask'])),
	            'router' => array_map('intval', explode('.', $confarray['gateway'])),
	            'dns_server' => array_map('intval', explode('.', $confarray['dnsserver'])),
	            'lease_time' => intval($confarray['leasetime']),
	            'domain_name' => $confarray['domainname']
	    );
	} else {
		return 0;
	};
    }
}
