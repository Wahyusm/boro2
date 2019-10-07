<?php

/**
 * Thanks To : Janu Yoga & Aan Ahmad
 * Date Share : 27-03-2019 (First Share)
 * Date Updated V.7 : 01 - Oktober - 2019
 * Created By : Will Pratama - facebook.com/yaelahhwil
**/

date_default_timezone_set("Asia/Jakarta");
class Marlboro extends modules
{
	public $fileCookies = "cookiesMarlboro.txt";
	protected $cookie;
	protected $modules;

	private function cookiesAkun()
	{
		$file = $this->fileCookies;
		foreach(explode("\n", str_replace("\r", "", file_get_contents($file))) as $a => $data)
		{
			$pecah = explode("|", trim($data));
			return array("decide_session" => trim($pecah[0]), "email" => trim($pecah[1]), "password" => trim($pecah[2]));
		}
	}

	public function generateData($cookies = false)
	{
		$url = 'https://www.marlboro.id/auth/login';
		if($cookies == "true")
		{
			$headers = explode("\n", "Host: www.marlboro.id\nSec-Fetch-Mode: navigate\nSec-Fetch-User: ?1\nSec-Fetch-Site: same-origin\nCookie: decide_session=".trim($this->cookiesAkun()['decide_session']));
		}else{
			$headers = explode("\n", "Host: www.marlboro.id\nSec-Fetch-Mode: navigate\nSec-Fetch-User: ?1\nSec-Fetch-Site: same-origin");
		}

		$generateData = $this->request($url, null, $headers, 'GET');
		$decide_session = $this->fetchCookies($generateData[1])['decide_session'];
		$decide_csrf = $this->getStr($generateData[0], '<input type="hidden" name="decide_csrf" value="', '"', 1, 0);
		@$device_id = $this->fetchCookies($generateData[1])['deviceId'];
		return array(
			trim($decide_session),
			trim($decide_csrf),
			trim($device_id),
		);
	}

	public function login($email, $password)
	{
		if(@file_exists($this->fileCookies) == true)
		{
			@unlink($this->fileCookies);
		}

		$generateData = $this->generateData();
		$url = "https://www.marlboro.id/auth/login";
		$headers = explode("\n","Host: www.marlboro.id\nX-Requested-With: XMLHttpRequest\nSec-Fetch-Mode: cors\nContent-Type: application/x-www-form-urlencoded; charset=UTF-8\nSec-Fetch-Site: same-origin\nCookie: deviceId=".$generateData[2]."; decide_session=".$generateData[0]);
		$post = 'email='.trim($email).'&password='.trim($password).'&ref_uri=/&decide_csrf='.$generateData[1].'&param=&exception_redirect=false';
		$login = $this->request($url, $post, $headers);
		if(strpos($login[0], '"message":"success"'))
		{
			   $decide_session = $this->fetchCookies($login[1])['decide_session'];
			//$device_id = $this->fetchCookies($login[1])['deviceId'];
	    	$this->fwrite($this->fileCookies, trim($decide_session)."|".trim($email)."|".trim($password));
		}

		return $login;
	}

	public function redirect(){
		$generateData = $this->generateData("true");
		$url = "https://www.marlboro.id/aldmic/catalog?_=";
		$headers = explode("\n","Host: www.marlboro.id\nX-Requested-With: XMLHttpRequest\nSec-Fetch-Mode: cors\nCookie: deviceId=".$generateData[2]."; token=".$generateData[1]."; decide_session=".$generateData[0]);
		$aldmic_redirect = $this->request($url, null, $headers, 'GET');
		$json_aldmic=json_decode($aldmic_redirect[0],true);
		$url_aldmic=$json_aldmic["data"]["url"];
		return $url_aldmic;
	}

	public function cfduid(){
		$url = $this->redirect();
		$headers = explode("\n","Host: loyalti.aldmic.com\nSec-Fetch-Mode: navigate\nReferer: https://www.marlboro.id/");
		$aldmic = $this->request($url, null, $headers, 'GET');
		
		$cfduid = $this->fetchCookies($aldmic[1])["__cfduid"];
		echo $cfduid;
		return $cfduid;
	}

	public function aldmic(){
		$cfduid = $this->cfduid();
		$url = $this->redirect();
		$headers = explode("\n","Host: loyalti.aldmic.com\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\nConnection: keep-alive\nSec-Fetch-Mode: navigate\nReferer: https://www.marlboro.id/\nCookie: __cfduid=".$cfduid);
		$aldmic = $this->request($url, null, $headers, 'GET');
		
		print_r($aldmic);
	}
		
	public function execute_login($email, $password)
	{
		for($o=1;$o<=10;$o++)
		{
			sleep(1);
			$login = $this->login($email, $password);
			if(strpos($login[0], '"code":200,"message":"success"'))
			{
				echo "LOGIN SUKSES\n";
				return false;
			}elseif(strpos($login[0], '"message":"Please Accept GoAheadPeople T&C"')){
				if(@file_exists($this->fileCookies) == true)
				{
					@unlink($this->fileCookies);
				}

				print PHP_EOL."Failed Login!, Message : Please Accept GoAheadPeople T&C.. Retry!";
			}elseif(strpos($login[0], '"message":"Email atau password yang lo masukan salah."')){
				if(@file_exists($this->fileCookies) == true)
				{
					@unlink($this->fileCookies);
				}

				print PHP_EOL."Email atau password yang lo masukan salah.";
				$myfile = fopen("error.txt", "a") or die("Unable to open file!");
				$txt = "{$email}|{$password}\n";
				fwrite($myfile, $txt);
				fclose($myfile);
				return false;
			}elseif(strpos($login[0], '"message":"Action is not allowed"')){
				if(@file_exists($this->fileCookies) == true)
				{
					@unlink($this->fileCookies);
				}

				print PHP_EOL."Action is not allowed\n".$login[0];
				$myfile = fopen("error.txt", "a") or die("Unable to open file!");
				$txt = "{$email}|{$password}\n";
				fwrite($myfile, $txt);
				fclose($myfile);
				return false;
			}elseif(strpos($login[0], 'Akun lo telah dikunci')){
				print PHP_EOL."[RESET PASSWORD] Akun lo telah dikunci karena gagal login berturut-turut.";
				return false;	
			}else{
				if(@file_exists($this->fileCookies) == true)
				{
					@unlink($this->fileCookies);
				}
				$myfile = fopen("error.txt", "a") or die("Unable to open file!");
				$txt = "{$email}|{$password}\n";
				fwrite($myfile, $txt);
				fclose($myfile);
				print PHP_EOL."Failed Login\n".$login[0];
				return false;
			}
		}
	}
}
class modules 
{
	public function request($url, $param, $headers, $request = 'POST') 
	{
		$ch = curl_init();
		$data = array(
				CURLOPT_URL				=> $url,
				CURLOPT_POSTFIELDS		=> $param,
				CURLOPT_HTTPHEADER 		=> $headers,
				CURLOPT_CUSTOMREQUEST 	=> $request,
				CURLOPT_HEADER 			=> true,
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_FOLLOWLOCATION 	=> true,
				CURLOPT_SSL_VERIFYPEER	=> false
			);
		curl_setopt_array($ch, $data);
		$execute = curl_exec($ch);
		$cookies = array();
		preg_match_all('/Set-Cookie:(?<cookie>\s{0,}.*)$/im', $execute, $cookies);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($execute, 0, $header_size);
		$body = substr($execute, $header_size);
		curl_close($ch);
		return [$body, $header, $cookies['cookie']];
	}

	public function getStr($page, $str1, $str2, $line_str2, $line)
	{
		$get = explode($str1, $page);
		$get2 = explode($str2, $get[$line_str2]);
		return $get2[$line];
	}

	public function fetchCookies($source) 
	{
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $source, $matches);
		$cookies = array();
		foreach($matches[1] as $item) 
		{
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		return $cookies;
	}

	public function fwrite($namafile, $data)
	{
		$fh = fopen($namafile, "a");
		fwrite($fh, $data);
		fclose($fh);  
	}
}	

$modules = new modules();
$marlboro = new marlboro();

//print $marlboro->Passion()."\n".$marlboro->getPoint();

print "\n[!] Script Created By: Will Pratama";
print "\n[!] Note: Jangan Run Lebih Dari 1 Terminal, Kecuali File Beda Folder!";
print "\n[!] Note: Diusahakan menggunakan IP Indonesia";
print "\n[!] @Version: V.7\n\n";

awal:
echo "Input FIle Akun Marlboro (Email|Pass) : ";
@$fileakun = trim(fgets(STDIN));

if(empty(@file_get_contents($fileakun)))
{
	print PHP_EOL."File Akun Tidak Ditemukan.. Silahkan Input Ulang".PHP_EOL;
	goto awal;
}

print PHP_EOL."Total Ada : ".count(explode("\n", str_replace("\r","",@file_get_contents($fileakun))))." Akun, Letsgo..";

while(true)
{
	echo PHP_EOL."Start Date : ".date("Y-m-d H:i:s");
	foreach(explode("\n", str_replace("\r", "", @file_get_contents($fileakun))) as $c => $akon)
	{	
		$pecah = explode("|", trim($akon));
		$email = trim($pecah[0]);
		$password = trim($pecah[1]);
		echo PHP_EOL.PHP_EOL.PHP_EOL."Ekse Akun : ".$email.PHP_EOL;
		print $marlboro->execute_login($email, $password);
		print $marlboro->redirect();
		print $marlboro->cfduid();
		print $marlboro->aldmic();

	print PHP_EOL."All Done Run!";
	sleep(1000);
	}
}

?>