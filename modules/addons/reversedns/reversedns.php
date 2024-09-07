<?php
if (!defined("WHMCS")) {
    die("Bu dosya doğrudan erişilemez");
}

function reversedns_config() {
    $configarray = array(
        "name" => "Reverse DNS Yönetimi",
        "description" => "Müşterilerin atanmış IP adresleri için reverse DNS kayıtlarını güncelleyebilmelerini sağlar.",
        "version" => "1.0",
        "author" => "Sizin İsminiz",
        "fields" => array(
            "api_url" => array(
                "FriendlyName" => "PowerDNS API URL",
                "Type" => "text",
                "Size" => "50",
                "Default" => "http://46.253.7.3:8082/api/v1",
                "Description" => "PowerDNS API'nin temel URL'si",
            ),
            "api_key" => array(
                "FriendlyName" => "PowerDNS API Anahtarı",
                "Type" => "password",
                "Size" => "50",
                "Default" => "LcCSBg6mF9rBSLHf",
                "Description" => "PowerDNS API anahtarınızı girin",
            ),
        ),
    );
    return $configarray;
}

function reversedns_activate() {
    return array('status' => 'success', 'description' => 'Modül başarıyla etkinleştirildi.');
}

function reversedns_deactivate() {
    return array('status' => 'success', 'description' => 'Modül başarıyla devre dışı bırakıldı.');
}

function reversedns_clientarea($vars) {
    $userid = $_SESSION['uid'];
    $apiUrl = $vars['api_url'];
    $apiKey = $vars['api_key'];

    // Müşterinin atanmış IP adreslerini al
    $result = localAPI('GetClientsProducts', array('clientid' => $userid));
    $ipAddresses = array();

    foreach ($result['products']['product'] as $product) {
        $dedicatedip = $product['dedicatedip'];
        if (!empty($dedicatedip)) {
            $ips = explode(',', $dedicatedip);
            foreach ($ips as $ip) {
                $ipAddresses[] = trim($ip);
            }
        }
    }

    // Form gönderildi mi?
    if ($_POST['update_rdns']) {
        $ipToUpdate = $_POST['update_rdns'];
        $newRDNS = $_POST['new_rdns'][$ipToUpdate];

        // Girdi doğrulama
        if (filter_var($ipToUpdate, FILTER_VALIDATE_IP) && preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $newRDNS)) {
            // PowerDNS API ile güncelle
            $ptrRecord = array(
                'rrsets' => array(
                    array(
                        'name' => getPTRDomain($ipToUpdate),
                        'type' => 'PTR',
                        'changetype' => 'REPLACE',
                        'records' => array(
                            array(
                                'content' => $newRDNS . '.',
                                'disabled' => false,
                            ),
                        ),
                    ),
                ),
            );

            $ch = curl_init($apiUrl . '/servers/localhost/zones/' . getZoneName($ipToUpdate));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-API-Key: ' . $apiKey,
                'Content-Type: application/json',
            ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ptrRecord));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                $success = 'Reverse DNS kaydı başarıyla güncellendi.';
            } else {
                $error = 'Güncelleme sırasında bir hata oluştu.';
            }
        } else {
            $error = 'Geçersiz IP adresi veya hostname.';
        }
    }

    // IP adresleri ve mevcut rDNS kayıtlarını al
    $ipData = array();
    foreach ($ipAddresses as $ip) {
        $ptrDomain = getPTRDomain($ip);
        $ch = curl_init($apiUrl . '/servers/localhost/zones/' . getZoneName($ip));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-API-Key: ' . $apiKey,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $zoneData = json_decode($response, true);

        $currentRDNS = '';
        if (isset($zoneData['rrsets'])) {
            foreach ($zoneData['rrsets'] as $rrset) {
                if ($rrset['name'] == $ptrDomain && $rrset['type'] == 'PTR') {
                    $currentRDNS = rtrim($rrset['records'][0]['content'], '.');
                }
            }
        }

        $ipData[] = array(
            'address' => $ip,
            'current_rdns' => $currentRDNS,
        );
    }

    // Şablona verileri gönder
    return array(
        'pagetitle' => 'Reverse DNS Yönetimi',
        'breadcrumb' => array('index.php?m=reversedns' => 'Reverse DNS Yönetimi'),
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => array(
            'ipaddresses' => $ipData,
            'error' => isset($error) ? $error : '',
            'success' => isset($success) ? $success : '',
        ),
    );
}

function getPTRDomain($ip) {
    $reverseOctets = array_reverse(explode('.', $ip));
    return implode('.', $reverseOctets) . '.in-addr.arpa.';
}

function getZoneName($ip) {
    $octets = explode('.', $ip);
    return $octets[0] . '.' . $octets[1] . '.' . $octets[2] . '.in-addr.arpa.';
}
?>
