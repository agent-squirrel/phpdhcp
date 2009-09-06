<?php

class dhcpPacket {
    public static $options = array(
        1 => array('name' => 'subnet_mask', 'type' => 'ip'),
        2 => array('name' => 'time_offset', 'type' => 'int'),
        3 => array('name' => 'router', 'type' => 'ip'),
        6 => array('name' => 'dns_server', 'type' => 'ip'),
        12 => array('name' => 'host_name', 'type' => 'string'),
        15 => array('name' => 'domain_name', 'type' => 'string'),
        28 => array('name' => 'broadcast_address', 'type' => 'ip'),
        50 => array('name' => 'requested_ip_address', 'type' => 'ip'),
        51 => array('name' => 'lease_time', 'type' => 'int'),
        53 => array('name' => 'message_type', 'type' => 'messageType'),
        54 => array('name' => 'server_id', 'type' => 'ip'),
        55 => array('name' => 'parameter_request', 'type' => 'binary'),
        57 => array('name' => 'max_message_size', 'type' => 'int'),
        58 => array('name' => 'renewal_time', 'type' => 'int'),
        59 => array('name' => 'rebinding_time', 'type' => 'int'),
        61 => array('name' => 'client_id', 'type' => 'mac')
    );
    public static $messageTypes = array(
        1 => 'discover',
        2 => 'offer',
        3 => 'request',
        4 => 'decline',
        5 => 'ack',
        6 => 'nak',
        7 => 'release',
        8 => 'inform'
    );
    public $packetData = array();
    
    function parse($binaryData) {
        $this->packetData = unpack("H2op/H2type/H2hlen/H2hops/H8xid/H4secs/H4flags/H8ciaddr/H8yiaddr/H8siaddr/H8giaddr/H32chaddr/H128sname/H256file/H8magic/H*options", $binaryData);
        
        $optionData = $this->packetData['options'];
        $pos = 0;
        while(strlen($optionData) > $pos) {
            $code = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $len = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $curoptdata = substr($optionData, $pos, $len*2);
            $pos += $len*2;

            $optinfo = dhcpPacket::$options[$code];
            if ($optinfo) {
                $translatedData = null;
                switch($optinfo['type']) {
                    case 'int':
                        $translatedData = base_convert($curoptdata, 16, 10);
                        break;
                    case 'string':
                        $translatedData = $this->hex2string($curoptdata);
                        break;
                    case 'ip':
                        $translatedData = array(
                            $this->hex2int($curoptdata[0] . $curoptdata[1]),
                            $this->hex2int($curoptdata[2] . $curoptdata[3]),
                            $this->hex2int($curoptdata[4] . $curoptdata[5]),
                            $this->hex2int($curoptdata[6] . $curoptdata[7])
                            );
                        break;
                    case 'messageType':
                        $translatedData = dhcpPacket::$messageTypes[$this->hex2int($curoptdata)];
                        break;
                    default:
                        $translatedData = $curoptdata;
                        break;
                }
                $this->packetData[$optinfo['name']] = $translatedData;
            } else {
                $this->packetData[$code] = $curoptdata;
            }
        }
    }
    
    function build() {
        $p = $this->packetData;
        
        $optionsData = '';
        foreach(dhcpPacket::$options as $optcode => $optinfo) {
            if (isset($this->packetData[$optinfo['name']])) {
                $itemdata = $this->packetData[$optinfo['name']];
                $code = $this->int2hex($optcode);
                
                switch($optinfo['type']) {
                    case 'int':
                        $translatedData = $this->int2hex($itemdata);
                        break;
                    case 'string':
                        $translatedData = $this->string2hex($itemdata);
                        break;
                    case 'ip':
                        $translatedData =
                            $this->int2hex($itemdata[0]) .
                            $this->int2hex($itemdata[1]) .
                            $this->int2hex($itemdata[2]) .
                            $this->int2hex($itemdata[3]);
                        break;
                    case 'messageType':
                        $translatedData = $this->int2hex(array_search($itemdata, dhcpPacket::$messageTypes));
                        break;
                    default:
                        $translatedData = $itemdata;
                        break;
                }
                
                // print_r($code . $this->int2hex(strlen($translatedData)/2) . $translatedData);
                $optionsData .= $code . $this->int2hex(strlen($translatedData)/2) . $translatedData;
            }
        }
        $optionsData .= $this->int2hex(255);
        
        $data = pack("H2H2H2H2H8H4H4H8H8H8H8H32H128H256H8H*",
            $p['op'],
            $p['type'],
            $p['hlen'],
            $p['hops'],
            $p['xid'],
            $p['secs'],
            $p['flags'],
            $p['ciaddr'],
            $p['yiaddr'],
            $p['siaddr'],
            $p['giaddr'],
            $p['chaddr'],
            $p['sname'],
            $p['file'],
            $p['magic'],
            $optionsData
        );
        
        return $data;
    }
    
    function string2hex($str) {
        $hex = '';
        for ($iter = 0; $iter < strlen($str); $iter++) {
            $hex .= dechex(ord($str[$iter]));
        }
        return $hex;
    }
    
    function hex2string($hex) {
        $str = '';
        for ($iter = 0; $iter < strlen($hex) - 1; $iter += 2) {
            $str .= chr(hexdec($hex[$iter] . $hex[$iter + 1]));
        }
        return $str;
    }
    
    function hex2int($hex) {
        return base_convert($hex, 16, 10);
    }
    
    function int2hex($int) {
        $hex = base_convert($int, 10, 16);
        if (strlen($hex) == 1) {
            $hex = '0' . $hex;
        } else if (strlen($hex) == 3) {
            $hex = '0' . $hex;
        }
        return $hex;
    }
}