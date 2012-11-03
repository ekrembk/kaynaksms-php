<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * KAYNAKSMS API PHP LIBRARY - Codeigniter
 * VER 5.6.0 sürümünde yazıldı ve test edildi
 * 
 * @author		Ekrem BÜYÜKKAYA
 * @copyright	Copyright (c) 2012, Ekrem BÜYÜKKAYA.
 * @link		http://ekrembk.com
 * @since		Version 0.9
 */

class Kaynaksms extends CI_Model {
	// API giriş bilgileri
	private $kullanici 	= 'KULLANICIADINIZ';
	private $sifre		= 'SIFRENIZ';
	private $baslik		= 'MESAJBASLIGINIZ';

	// Kullanıcıya göre değişiklik gösterebilir
	private $maksimum_karakter = 459;
	
	// Telefon numarası
	private $telefon	= NULL;
	private $mesaj 		= NULL;

	// Sistem sabitleri
	private $hatalar	= array(
							'01' => 'Mesaj gönderim başlangıç tarihinde hata var. Sistem tarihi ile değiştirilip gönderildi.',
							'02' => 'Mesaj gönderim sonlandırılma tarihinde hata var.Sistem tarihi ile değiştirilip gönderildi.',
							'10' => 'Telefon numarası hatalı.',
							'20' => 'Mesaj metninde boş olmasından veya maksimum mesaj karakterini geçilmesi.',
							'30' => 'Kullanıcı bilgisi bulunamadı.',
							'40' => 'Geçersiz mesaj başlığı. (başlık sisteme tanımlanmamış)',
							'50' => 'Kullanıcının kredisi yok.',
							'60' => 'Telefon numarası hiç tanımlanmamış.',
							'70' => 'Mesaj başlığı hatalı'
						);
	public $hata 		= FALSE;

	// Gönderilecek telefonu belirle
	function telefon( $telefon ) {
		$this->telefon = $telefon;

		return $this;
	}

	// Mesajı belirle
	function mesaj( $mesaj ) {
		$this->mesaj = $mesaj;

		return $this;
	}

	// Mesajı gönder
	function gonder() {
		// Kontroller
		if( ! $this->kullanici OR ! $this->sifre )
			$this->hata = 'API erişimi için kullanıcı ve şifre atanmamış.';

		// Girilen telefon numaralasını kontrol et
		if( ! $this->telefon )
			$this->hata = 'Gönderilecek telefon numarası girilmemiş.';

		// Sadece sayılardan oluşmalı ve 12 karakter olmalı geçerli bir telefon
		if( ! preg_match( '/^[0-9]*$/iU', $this->telefon ) OR strlen( $this->telefon ) != 12 )
			$this->hata = "Geçersiz telefon numarası: {$this->telefon}";

		// Mesajı kontrol et
		if( ! $this->mesaj )
			$this->hata = 'Gönderilecek mesaj belirlenmedi.';

		// Maksimum karakter kontrolü
		if( $this->mesaj && strlen( $this->mesaj ) > $this->maksimum_karakter )
			$this->hata = "Bir SMS {$this->maksimum_karakter} karakterden fazla olamaz.";

		// Hata kontrolü
		if( $this->hata )
			return FALSE;

		// Kaynaksms'ten sonuç al
		$sonuc = $this->_sonuc();

		// Hata mesajlarını kontrol et
		$sonuc_parcala = explode( ' ', $sonuc );

		// İşlem başarılı
		if( ( $sonuc_parcala[0] === '00' OR $sonuc_parcala[0] === '01' OR $sonuc_parcala[0] === '02' ) && isset( $sonuc_parcala[1] ) ):
			// SMS ID'sini döndür
			return $sonuc_parcala[1];

		else:
			// Bizdeki hata listesinde var mı
			if( isset( $this->hatalar[$sonuc_parcala[0]] ) )
				$this->hata = $this->hatalar[$sonuc_parcala[0]];

			// Direkt sonucu hata olarak kaydet
			else
				$this->hata = "Bilinmeyen hata: {$sonuc}";

			return FALSE;

		endif;
	}

	// Gönderim denemesi
	function _sonuc() {
		// API gönderim URL'sini oluştur
		$url = $this->_url();

		// Bağlantı
		$ch = curl_init( $url );

		// Ayarlar
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, TRUE );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );

		// Döngüyü al
		$veri = curl_exec( $ch );

		// Bağlantıyı kapat
		curl_close( $ch );

		return $veri;	
	}

	// API URL
	function _url() {
		// Parametreler
		$parametre = array(
				'usercode'	=> $this->kullanici,
				'password' 	=> $this->sifre,
				'gsmno'		=> $this->telefon,
				'message'	=> $this->mesaj,
				'msgheader' => $this->baslik,
				'startdate' => '',
				'stopdate'	=> ''
			);

		// Temel URL
		$url = 'http://api.netgsm.com.tr/bulkhttppost.asp';

		// Birleştir
		return $url . '?' . http_build_query( $parametre );
	}
}