<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly.");
}

function reversedns_config() {
    $configarray = array(
        "name" => "Reverse DNS Management",
        "description" => "Allows customers to update reverse DNS records for assigned IP addresses.",
        "version" => "1.1",
        "author" => "7DC.NET",
        "fields" => array(
            "api_url" => array(
                "FriendlyName" => "PowerDNS API URL",
                "Type" => "text",
                "Size" => "50",
                "Default" => "http://46.253.7.3:8082/api/v1",
                "Description" => "PowerDNS API base URL",
            ),
            "api_key" => array(
                "FriendlyName" => "PowerDNS API Keys",
                "Type" => "password",
                "Size" => "50",
                "Default" => "",
                "Description" => "Enter your PowerDNS API key",
            ),
        ),
    );
    return $configarray;
}

function reversedns_activate() {
    return array('status' => 'success', 'description' => 'The module has been successfully activated.');
}

function reversedns_deactivate() {
    return array('status' => 'success', 'description' => 'The module has been successfully disabled.');
}

function reversedns_clientarea($vars) {
    $userid = $_SESSION['uid']; // Oturum açmış kullanıcının ID'sini al
    $apiUrl = $vars['api_url'];
    $apiKey = $vars['api_key'];

    // URL'den seçili serviceid'yi al
    $selectedServiceId = isset($_GET['serviceid']) ? (int)$_GET['serviceid'] : null;

    // Müşterinin ürünlerini al ve seçilen ürün ID'sine ait IP adreslerini filtrele
    $ipAddresses = array();
    $result = localAPI('GetClientsProducts', array('clientid' => $userid));
    if (isset($result['products']['product'])) {
        foreach ($result['products']['product'] as $product) {
            if ($selectedServiceId && $product['id'] != $selectedServiceId) {
                continue; // Eğer serviceid uyuşmuyorsa atla
            }

            if ($product['status'] === 'Active') {
                $dedicatedip = $product['assignedips'];
                if (!empty($dedicatedip)) {
                    $ips = explode(',', $dedicatedip);
                    foreach ($ips as $ip) {
                        $ip = trim($ip);
                        // Sadece IPv4 adreslerini al
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $ipAddresses[] = $ip;
                        }
                    }
                }
            }
        }
    } else {
        $error = 'IP adresleri alınamadı.';
    }

    // Form gönderildi mi?
    if (isset($_POST['update_rdns'])) {
        $ipToUpdate = $_POST['update_rdns'];
        $newRDNS = $_POST['new_rdns'][$ipToUpdate];

        // Girdi doğrulama
        if (filter_var($ipToUpdate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/', $newRDNS)) {
            // PowerDNS API ile güncelle
            $ptrRecord = array(
                'rrsets' => array(
                    array(
                        'name' => getPTRDomain($ipToUpdate),
                        'type' => 'PTR',
                        'changetype' => 'REPLACE',
                        'ttl' => 3600, // TTL değerini ekleyin
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
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ptrRecord));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                $error = 'CURL Error: ' . curl_error($ch);
            } else if ($httpCode == 204) {
                $success = 'The Reverse DNS record has been updated successfully.';
                // Başarı mesajını gösterip yönlendirmeyi JavaScript ile yapacağız
                $redirect = true;
            } else {
                $error = 'An error occurred during the update. HTTP Code: ' . $httpCode . ' Response: ' . $response;
            }
            curl_close($ch);
        } else {
            $error = 'Invalid IP address or hostname.';
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'CURL Error: ' . curl_error($ch);
        } else if ($httpCode != 200) {
            $error = 'Zone information could not be obtained. HTTP Code: ' . $httpCode . ' Response: ' . $response;
            $zoneData = array(); // Zone bilgisi alınamadıysa boş dizi atama
        } else {
            $zoneData = json_decode($response, true);
        }
        curl_close($ch);

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
    $vars = array(
        'ipaddresses' => $ipData,
        'error' => isset($error) ? $error : '',
        'success' => isset($success) ? $success : '',
        'redirect' => isset($redirect) ? $redirect : false,
    );

    return array(
        'pagetitle' => 'Reverse DNS Management',
        'breadcrumb' => array('index.php?m=reversedns' => 'Reverse DNS Management'),
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => $vars,
    );
}

function getPTRDomain($ip) {
    $reverseOctets = array_reverse(explode('.', $ip));
    return implode('.', $reverseOctets) . '.in-addr.arpa.';
}

function getZoneName($ip) {
    $octets = explode('.', $ip);
    if (count($octets) == 4) {
        // Bu örnek, sadece /24 subnet maskesi için doğru çalışır.
        return $octets[2] . '.' . $octets[1] . '.' . $octets[0] . '.in-addr.arpa.';
    }
    return ''; // Geçersiz IP formatı
}


?>
