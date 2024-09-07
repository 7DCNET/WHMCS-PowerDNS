### Kurulum Talimatları

**1. Modül Dosyalarını Yükleyin:**

- modules/addons/reversedns/ dizinini oluşturun.
- Yukarıdaki kodları kullanarak aşağıdaki dosyaları oluşturun ve bu dizine yerleştirin:
	- reversedns.php
	- hooks.php
- modules/addons/reversedns/templates/ dizinini oluşturun ve içine clientarea.tpl dosyasını koyun.

**2. Modülü Etkinleştirin:**

- WHMCS yönetici paneline giriş yapın.
- **Eklentiler (Addons) > Eklenti Modülleri (Addon Modules)** bölümüne gidin.
- "Reverse DNS Yönetimi" modülünü bulun ve **Etkinleştir (Activate)** butonuna tıklayın.
- **Yapılandır (Configure)** seçeneğine tıklayarak PowerDNS API URL'sini ve API anahtarınızı girin:
	- PowerDNS API URL: http://powerdns-server/api/v1
	- PowerDNS API Anahtarı: changeme 

**1. Yan Menüye "Reverse DNS" Linki Ekleme**
Müşterinin sunucu hizmeti sayfasına, yan menüde bir "Reverse DNS" linki eklemek için WHMCS'in "Server Details" sayfasını hedefleyen bir hook kullanabiliriz. Bu, müşterinin sunucu hizmetine girdiğinde yan menüde "Reverse DNS" seçeneğinin görünmesini sağlar.

hooks.php **Dosyasına Gerekli Hook'u Ekleyin**
Bu hook, sunucunun detay sayfasına bir "Reverse DNS" menü öğesi ekleyecek.

```php
<?php
add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    // Müşteri oturum açmış mı?
    if (!isset($_SESSION['uid'])) {
        return;
    }

    // Sunucu ürünleri sayfasındaysak
    if ($primarySidebar->getChild('Service Details Actions')) {
        // Yan menüye Reverse DNS öğesi ekleyelim
        $reverseDNSMenuItem = $primarySidebar->getChild('Service Details Actions')->addChild('reverse_dns', array(
            'label' => 'Reverse DNS',
            'uri' => 'index.php?m=reversedns&serviceid=' . (int) $_GET['id'],
            'order' => 15, // Menüdeki sıralama
        ));
    }
});
?>

```

Bu kod sayesinde müşteri, sunucu hizmeti detaylarına girdiğinde yan menüde "Reverse DNS" linkini görecek.

**4. Gereksinimler:**

- Sunucunuzda cURL ve PHP JSON desteğinin etkin olduğundan emin olun.
- PowerDNS API'sine erişimin doğru şekilde yapılandırıldığından emin olun.

**Önemli Notlar**
**IP Adreslerini Alma:** Kod, müşterinin ürünlerindeki dedicatedip alanını kullanarak IP adreslerini alır. Eğer IP adresleri farklı bir alanda tutuluyorsa, kodu buna göre düzenlemelisiniz.

**Güvenlik ve Doğrulama:** Modül, müşterinin sadece kendi IP adreslerini görmesini ve güncellemesini sağlar. Ancak ek güvenlik kontrolleri eklemek isterseniz kodu geliştirebilirsiniz.

**Hostname Doğrulaması:** Girilen hostname'in geçerli bir alan adı formatında olup olmadığını kontrol eden basit bir regex kullanılmıştır. Daha kapsamlı doğrulamalar ekleyebilirsiniz.

**PTR Domain ve Zone Hesaplaması:** IPv4 adresleri için in-addr.arpa kullanılmıştır. Eğer IPv6 desteği eklemek isterseniz kodu güncellemelisiniz.
