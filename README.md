## Özet Kurulum Adımları

1. **Dosyaları Yükleyin:**

	- modules/addons/reversedns/reversedns.php
	- modules/addons/reversedns/hooks.php
	- modules/addons/reversedns/templates/clientarea.tpl

2. **Menüye Reverse DNS Linkini Ekleyin:**

	- hooks.php dosyasına yukarıdaki kodu ekleyerek sunucu hizmet detay sayfasına "Reverse DNS" menüsünü ekleyin.

**3. Modülü Etkinleştirin:**

- WHMCS yönetici paneline giriş yapın.
- **Eklentiler (Addons) > Eklenti Modülleri (Addon Modules)** bölümüne gidin.
- "Reverse DNS Yönetimi" modülünü bulun ve **Etkinleştir (Activate)** butonuna tıklayın.
- **Yapılandır (Configure)** seçeneğine tıklayarak PowerDNS API URL'sini ve API anahtarınızı girin:
	- PowerDNS API URL: http://powerdns-server:8081/api/v1
	- PowerDNS API Anahtarı: changeme 

**4. Gereksinimler:**

- Sunucunuzda cURL ve PHP JSON desteğinin etkin olduğundan emin olun.
- PowerDNS API'sine erişimin doğru şekilde yapılandırıldığından emin olun.

**Önemli Notlar**
**IP Adreslerini Alma:** Kod, müşterinin ürünlerindeki dedicatedip alanını kullanarak IP adreslerini alır. Eğer IP adresleri farklı bir alanda tutuluyorsa, kodu buna göre düzenlemelisiniz.

**Güvenlik ve Doğrulama:** Modül, müşterinin sadece kendi IP adreslerini görmesini ve güncellemesini sağlar. Ancak ek güvenlik kontrolleri eklemek isterseniz kodu geliştirebilirsiniz.

**Hostname Doğrulaması:** Girilen hostname'in geçerli bir alan adı formatında olup olmadığını kontrol eden basit bir regex kullanılmıştır. Daha kapsamlı doğrulamalar ekleyebilirsiniz.

**PTR Domain ve Zone Hesaplaması:** IPv4 adresleri için in-addr.arpa kullanılmıştır. Eğer IPv6 desteği eklemek isterseniz kodu güncellemelisiniz.
