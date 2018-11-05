<?php

/**
 * Class Line
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2018 FIAND T
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	LINE BOT ONGKIR
 * @author	fiand96
 * @copyright	FIAND T
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @since	Version 1.0.0
 * @filesource
 */

 class Line extends Conn
 {
     private $channelAccessToken = ONGKIR['LINE_ACCESS_TOKEN'];
     private $channelSecret = ONGKIR['CHANNEL_SECRET'];
     private $webhookResponse;
     private $webhookEventObject;
     private $apiReply = "https://api.line.me/v2/bot/message/reply";
     private $apiPush = "https://api.line.me/v2/bot/message/push";
     public $kurir = array('jne','tiki','pos');
     private $part;
     private $message;
     private $id;

     public function __construct($chat)
     {
         $this->webhookEventObject = $chat;
         $this->id = $this->getUserId();
         $this->part = explode(' ', strtolower($this->getMessageText()));
         parent::__construct();
     }

     public function check_command()
     {
         switch ($this->part[0]) {
         case '/start':
           if (count($this->part) == 1) {
               $msg = $this->comm_start();
           } else {
               $msg = $this->unvalid_command();
           }
           break;
         case '/help':
           if (count($this->part) == 1) {
               $msg = $this->comm_help();
           } else {
               $msg = $this->unvalid_command();
           }
           break;
           case '/kodepos':
           if (count($this->part) >= 2) {
               $msg = $this->comm_kodepos();
           } else {
               $msg = $this->unvalid_command();
           }
           break;
           case '/ongkir':
           if (count($this->part) == 5) {
               $msg = $this->comm_cekongkir();
           } else {
               $msg = $this->unvalid_command();
           }
           break;
         default:
           $msg = $this->unregister_command();
           break;
       }
         return $msg;
     }

     public function comm_start()
     {
         $this->message = "Halo ini adalah bot untuk cek ongkos kirim. Bot ini masih dalam tahap pengembangan, ";
         $this->message .= "jadi masih terdapat banyak kekurangan. Silahkan kontak @fiand96 selaku pengembang jika terdapat masalah.";
         $this->message .= "\n\nKetik /help untuk melihat bantuan.";
         return $this->sendMessage();
     }

     public function comm_help()
     {
         $this->message = "\n\nUntuk mengecek ongkir format yang digunakan adalah :";
         $this->message .= "\n\n/ongkir A(SPASI)T(SPASI)B(SPASI)K";
         $this->message .= "\n\nA = Kode POS Asal";
         $this->message .= "\nT = Kode POS Tujuan";
         $this->message .= "\nB = Berat Paket (dalam Gram)";
         $this->message .= "\nK = Kurir (JNE, TIKI, atau POS)";
         $this->message .= "\n\nContoh : /ongkir 23681 55222 5000 JNE";
         $this->message .= "\n\nUntuk mencari kode pos format yang digunakan adalah :";
         $this->message .= "\n\n/kodepos KOTA/KABUPATEN";
         $this->message .= "\n\nContoh : /kodepos Yogyakarta";
         return $this->sendMessage();
     }

     public function comm_cekongkir()
     {
         $asal = $this->conn->query("SELECT city_id, city_name FROM city WHERE postal_code='".$this->part[1]."'");
         $num_asal = $asal->num_rows;
         $tujuan = $this->conn->query("SELECT city_id, city_name FROM city WHERE postal_code='".$this->part[2]."'");
         $num_tujuan = $tujuan->num_rows;

         if ($num_asal == 0) {
             $this->message = "Kota Asal tidak ditemukan, pastikan Kode POS yang dimasukkan benar dan pastikan itu Kode POS Kota/Kabupaten";
         } elseif ($num_tujuan == 0) {
             $this->message = "Kota Tujuan tidak ditemukan, pastikan Kode POS yang dimasukkan benar dan pastikan itu Kode POS Kota/Kabupaten";
         } elseif (! filter_var($this->part[3], FILTER_VALIDATE_INT)) {
             $this->message = "Berat paket kiriman harus berupa angka";
         } elseif ($this->part[3] < 100) {
             $this->message = "Berat minimal paket adalah 100 Gram";
         } elseif ($this->part[3] > 30000) {
             $this->message = "Berat maksimal paket adalah 30000 Gram (30Kg)";
         } elseif (! in_array(strtolower($this->part[4]), $this->kurir)) {
             $this->message = "Kurir yang anda minta tidak tersedia, Kami hanya mendukung JNE, TIKI, dan POS.";
         } else {
             $rajaongkir = new RajaOngkir();
             $content = $rajaongkir->cost($this->part[1], $this->part[2], $this->part[3], strtolower($this->part[4]));
             $data = json_decode($content, true);

             if ($data['rajaongkir']['status']['code'] == 200) {
                 $h = $data['rajaongkir']['results'];
                 $r = $asal->fetch_assoc();
                 $w = $tujuan->fetch_assoc();

                 $this->message = "Ongkir ".$r['city_name'] ." - ". $w['city_name']. " dengan berat ";
                 $this->message .= $this->part[3]." Gram menggunakan " . strtoupper($this->part[4]) . " adalah :\n\n";
                 foreach ($h as $v) {
                     if (count($v['costs']) > 0) {
                         foreach ($v['costs'] as $s) {
                             foreach ($s['cost'] as $q) {
                                 $this->message .= "Nama Service : ". ucwords($s['description']) . " (".$s['service'].") ";
                                 $this->message .= "\nPerkiraan sampai (hari) : " . r($q['etd']);
                                 $this->message .= "\nBiaya : " . rp($q['value'])."\n\n";
                             }
                         }
                     } else {
                         $this->message = "Maaf " . strtoupper($this->part[4]). " tidak tersedia untuk pengiriman ini";
                     }
                 }
             } else {
                 $this->message = "Maaf Terdapat kesalahan dalam sistem kami, coba ulangi beberapa saat lagi";
             }
         }
         $this->sendMessage();
     }

     public function comm_kodepos()
     {
         if (strlen($this->part[1]) < 4) {
             $this->message = "Untuk mencari Kode POS karakter minimum yang harus dimasukkan adalah 4";
         } elseif (! ctype_alpha($this->part[1])) {
             $this->message = "Nama Kota/Kabupaten hanya boleh berupa Alphabet (A-Z atau a-z)";
         } else {
             $query = "SELECT city_id, city_name, type, province, postal_code FROM city WHERE city_name LIKE '%".$this->part[1]."%'";

             if (count($this->part) == 3) {
                 $query = "SELECT city_id, city_name, type, province, postal_code FROM city WHERE city_name LIKE '%".$this->part[1].' '.$this->part[2] ."%'";
             }

             $kodepos = $this->conn->query($query);
             $num = $kodepos->num_rows;

             if ($num == 0) {
                 $this->message = "Kota yang dimaksud tidak ditemukan";
             } elseif ($num == 1) {
                 while ($row = $kodepos->fetch_assoc()) {
                     $this->message = $row['postal_code'] . " - " . "(" . $row['type'] . ")" . " ". $row['city_name']. ", " . $row['province'];
                 }
             } else {
                 $this->message = "Ditemukan beberapa hasil dari pencarian kamu : \n\n";
                 while ($row = $kodepos->fetch_assoc()) {
                     $this->message  .= $row['postal_code'] . " - " . "(" . $row['type'] . ")" . " ". $row['city_name']. ", " . $row['province']. "\n";
                 }
             }
         }
         $this->sendMessage();
     }

     public function unregister_command()
     {
         $this->message = "Perintah yang kamu masukkan tidak ditemukan, ketikan /help untuk bantuan.";
         $this->sendMessage();
     }

     public function unvalid_command()
     {
         $this->message = "Perintah yang kamu masukkan salah, ketikan /help untuk bantuan.";
         $this->sendMessage();
     }

     private function httpPost($api, $body)
     {
         $ch = curl_init($api);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/json; charser=UTF-8',
         'Authorization: Bearer '.$this->channelAccessToken));
         $result = curl_exec($ch);
         curl_close($ch);
         return $result;
     }

     public function sendMessage()
     {
         $api = $this->apiReply;
         $webhook = $this->webhookEventObject;
         $replyToken = $webhook->{"events"}[0]->{"replyToken"};
         $body["replyToken"] = $replyToken;
         $body["messages"][0] = array(
             "type" => "text",
             "text"=> $this->message
         );

         $result = $this->httpPost($api, $body);
         return $result;
     }

     public function push($body)
     {
         $api = $this->apiPush;
         $result = $this->httpPost($api, $body);
         return $result;
     }
     public function pushText($to, $text)
     {
         $body = array(
             'to' => $to,
             'messages' => [
             array(
                 'type' => 'text',
                 'text' => $text
             )
             ]
         );
         $this->push($body);
     }
     public function pushImage($to, $imageUrl, $previewImageUrl = false)
     {
         $body = array(
             'to' => $to,
             'messages' => [
             array(
                 'type' => 'image',
                 'originalContentUrl' => $imageUrl,
                 'previewImageUrl' => $previewImageUrl ? $previewImageUrl : $imageUrl
             )
             ]
         );
         $this->push($body);
     }
     public function pushVideo($to, $videoUrl, $previewImageUrl)
     {
         $body = array(
                 'to' => $to,
                 'messages' => [
                     array(
                 'type' => 'video',
                 'originalContentUrl' => $videoUrl,
                 'previewImageUrl' => $previewImageUrl
             )
             ]
         );
         $this->push($body);
     }
     public function pushAudio($to, $audioUrl, $duration)
     {
         $body = array(
             'to' => $to,
             'messages' => [
             array(
                 'type' => 'audio',
                 'originalContentUrl' => $audioUrl,
                 'duration' => $duration
             )
             ]
         );
         $this->push($body);
     }
     public function pushLocation($to, $title, $address, $latitude, $longitude)
     {
         $body = array(
             'to' => $to,
             'messages' => [
             array(
                 'type' => 'location',
                 'title' => $title,
                 'address' => $address,
                 'latitude' => $latitude,
                 'longitude' => $longitude
             )
             ]
         );
         $this->push($body);
     }

     public function getMessageText()
     {
         $webhook = $this->webhookEventObject;
         $messageText = $webhook->{"events"}[0]->{"message"}->{"text"};
         return $messageText;
     }

     public function postbackEvent()
     {
         $webhook = $this->webhookEventObject;
         $postback = $webhook->{"events"}[0]->{"postback"}->{"data"};
         return $postback;
     }

     public function cekuser()
     {
         $query = "SELECT * FROM user_bot where user_id = '".$this->id."'";
         $res = $this->conn->query($query);

         if ($res->num_rows ==0) {
             $this->data  = false;
         } else {
             $this->data  = $res->fetch_object();
         }
     }

     public function getUserId()
     {
         $webhook = $this->webhookEventObject;
         $userId = $webhook->{"events"}[0]->{"source"}->{"userId"};
         return $userId;
     }
 }
