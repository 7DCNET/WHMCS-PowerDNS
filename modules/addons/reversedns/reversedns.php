<?php
if (!defined("WHMCS")) {
    die("Bu dosyaya doğrudan erişim sağlanamaz.");
}

function reversedns_config() {
    $configarray = array(
        "name" => "Reverse DNS Yönetimi",
        "description" => "Müşterilerin atanmış IP adresleri için reverse DNS kayıtlarını güncelleyebilmelerini sağlar.",
        "version" => "1.1",
        "author" => "Sizin İsminiz",
        "fields" => array(
            "api_url" => array(
                "FriendlyName" => "PowerDNS API URL",
                "Type" => "text",
                "Size" => "50",
                "Default" => "http://46.253.7.3:8081/api/v1",
                "Description" => "PowerDNS API'nin temel URL'si",
            ),
            "api_key" => array(
                "FriendlyName" => "PowerDNS API Anahtarı",
                "Type" => "password",
                "Size" => "50",
                "Default" => "changeme",
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
    $userid = $_SESSION['uid']; // Oturum açmış kullanıcının ID'sini al
    $apiUrl = $vars['api_url'];
    $apiKey = $vars['api_key'];

    // Müşterinin aktif ürünlerini al
    $result = localAPI('GetClientsProducts', array('clientid' => $userid));
    if (isset($result['products']['product'])) {
        $ipAddresses = array();
        foreach ($result['products']['product'] as $product) {
            if ($product['status'] === 'Active') {
                $dedicatedip = $product['dedicatedip'];
                if (!empty($dedicatedip)) {
                    $ips = explode(',', $dedicatedip);
                    foreach ($ips as $ip) {
                        $ipAddresses[] = trim($ip);
                    }
                }
            }
        }
    } else {
        $error = 'IP adresleri alınamadı.';
        $ipAddresses = array();
    }

    // Form gönderildi mi?
    if (isset($_POST['update_rdns'])) {
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
                $success = 'Reverse DNS kaydı başarıyla güncellendi.';
                // Başarı mesajını gösterip yönlendirmeyi JavaScript ile yapacağız
                $redirect = true;
            } else {
                $error = 'Güncelleme sırasında bir hata oluştu. HTTP Kodu: ' . $httpCode . ' Yanıt: ' . $response;
            }
            curl_close($ch);
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'CURL Error: ' . curl_error($ch);
        } else if ($httpCode != 200) {
            $error = 'Zone bilgisi alınamadı. HTTP Kodu: ' . $httpCode . ' Yanıt: ' . $response;
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
        'pagetitle' => 'Reverse DNS Yönetimi',
        'breadcrumb' => array('index.php?m=reversedns' => 'Reverse DNS Yönetimi'),
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
