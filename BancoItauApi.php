<?php
/**
 * This class deals with:
 * - Definitive certificate generation with Itaú API
 * - Manage PIX Webhook address in the Itaú API
 */

class BancoItauApi {

  private $webhook_url = 'https://your_server_address/webhooks/pix/itau';
  private $client_id;
  private $client_secret;
  private $access_token;
  private $crt_path;
  private $key_path;
  private $pix_webhook_status;


  public function __construct( $client_id, $client_secret, $pix_key, $crt_path, $key_path ) {

    if ( !function_exists( 'curl_init' ) ) {
    
      throw new Exception( 'O PHP do servidor onde a aplicação está sendo executada não tem suporte ao CURL. Adicione suporte ao CURL e tente novamente.' );
    
    }

    $this->setClientId( $client_id );

    $this->setClientSecret( $client_secret );

    $this->setPixKey( $pix_key );

    $this->setCrtPath( $crt_path );

    $this->setKeyPath( $key_path );

    $this->getAccessToken();

  }


  private function setClientId( $id ) {

    if ( empty( $id ) ) {

      throw new Exception( 'O clientId não foi informado!' );

    }

    $this->client_id = $id;

  }


  private function setClientSecret( $secret ) {

    if ( empty( $secret ) ) {

      throw new Exception( 'O clientSecret não foi informado!' );

    }

    $this->client_id = $secret;

  }


  private function setPixKey( $key ) {

    if ( empty( $key ) ) {

      throw new Exception( 'A chave PIX não foi informada!' );

    }

    $this->pix_key = $key;

  }


  private function setCrtPath( $path ) {

    if ( empty( $path ) ) {

      throw new Exception( 'O caminho para o arquivo do certificado não foi informado!' );

    }

    $this->crt_path = $path;

  }


  private function setKeyPath( $path ) {

    if ( empty( $path ) ) {

      throw new Exception( 'O caminho para o arquivo da chave não foi informado!' );

    }

    $this->key_path = $path;

  }


  private function getAccessToken() {

      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, "https://sts.itau.com.br/api/oauth/token");
      curl_setopt($ch, CURLOPT_PORT , 443);
      curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
      curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
      curl_setopt($ch, CURLOPT_CAINFO, $this->crt_path);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_VERBOSE, 0);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id=".$this->client_id."&client_secret=".$this->client_secret);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/x-www-form-urlencoded"
      ));
      
      $response = curl_exec($ch);

      
      if ( !$response ) {
      
        throw new Exception( 'Houve um erro ao tentar obter o access token com o servidor de autenticação do Itaú!' );
      
      }

      
      $response = json_decode($response);

      /**
       * O objeto com o retorno contém os seguintes parâmetros:
       * $response->access_token
       * $response->token_type
       * $response->expires_in
       * $response->refresh_token
       * $response->scope
       * $response->active
       */


      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ( $http_code != 200 ) {
      
        throw new Exception( 'Houve um problema ao tentar obter o access token com o servidor de autenticação do Itaú!<br>Código HTTP: <span style="color: black;">'.$http_code.'</span><br>Mensagem: <span style="color: black;">'.$response->message.'</span>' );
      
      }

      $this->access_token;

  }


  public function setPixWebhookStatus( $status ) {
  
    if ( !in_array( $status, array( 0, 1 ) ) ) {
    
      throw new Exception( 'A situação informada para as notificações de pagamentos PIX da API Itaú não é válida!' );
    
    }

    if ( $status != $this->pix_webhook_status ) {

      if ( $status === 1 ) {
      
        $this->setPixWebhookEnabled();

      } else {
      
        $this->setPixWebhookDisabled();
      
      }

      $this->pix_webhook_status = $status;

    }

  }


  private function setPixWebhookEnabled() {

    $json = json_encode( array( 'webhookUrl' => $this->webhook_url ) );


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/webhook/'.$this->pix_key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 201 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar cadastrar o endereços das notificações PIX no Itaú.<br>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ) );
      
    }
  
  }


  private function setPixWebhookDisabled() {

    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/webhook/'.$this->chave_pix);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 204 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar desativar as notificações de pagamentos PIX na conta Itaú.<br>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ) );
      
    }
  
  }


  public function getPixWebhookStatus() {

    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/webhook/'.$this->pix_key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0); // Só ativar para depuração
    curl_setopt($ch, CURLOPT_HEADER, 0); // Só ativar para depuração
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Fornece a resposta da URL e não apenas true
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar consultar a situação das notificações PIX no Itaú.<br>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ) );
      
    } else {

      $output = json_decode( $output );

      if ( $output->webhookUrl ) {

        return true;

      } else {
      
        return false;
      
      }

    }
  
  }


  public function getPixWebhookAddress() {

    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/webhook/'.$this->pix_key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0); // Só ativar para depuração
    curl_setopt($ch, CURLOPT_HEADER, 0); // Só ativar para depuração
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Fornece a resposta da URL e não apenas true
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar obter a URL das notificações PIX no Itaú.<br>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ) );
      
    } else {

      $output = json_decode( $output );

      if ( $output->webhookUrl ) {

        return $output->webhookUrl;

      } else {
      
        return false;
      
      }

    }
  
  }


  /**
   * This public static method generates the Itaú API public access certificate using
   * the CSR and temporary access token received from the bank.
   * @param string $csr_content        csr file content
   * @param string $temp_access_token  temporary access token
   */
  public static function setGenerateCertificate( $csr_content, $temp_access_token ) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://sts.itau.com.br/seguranca/v1/certificado/solicitacao");
    curl_setopt($ch, CURLOPT_PORT , 443);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $csr_content);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: text/plain",
        'Authorization: Bearer '.$temp_access_token
      ));

    $certificado = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( $http_code != '200' ) {
    
      $mensagem = json_decode( $certificado );
      throw new Exception( 'Houve um problema ao tentar gerar o arquivo do certificado.<br>Código da resposta do servidor: <span style="color: black;">'.$http_code.'</span><br>Código de erro Itaú: <span style="color: black;">'.$mensagem->error.'</span><br>Mensagem: <span style="color: black;">'.$mensagem->message.'</span>' );
    
    }

    curl_close($ch);

    // Separa a resposta em linhas em um array
    $lines = preg_split('#\r?\n#', $certificado, 0);

    // Get client_secret
    $client_secret = explode( ':', $lines[0] );
    $client_secret = trim( $client_secret[1] );

    // Set certificate file location and name
    $filename = 'certificado_api_itau.crt';
    
    if ( !file_put_contents( $filename, $certificado ) ) {

      throw new Exception( "Houve um problema ao gravar o arquivo do certificado!" );

    }

    
    // Write clinet secret to a fileSet certificate file location and nae
    $cs_filename = 'client_secret_api_itau.txt';
    
    if ( !file_put_contents( $cs_filename, $client_secret ) ) {

      throw new Exception( "Houve um problema ao gravar o client secret em um arquivo, você pode encontrá-lo nas primeiras linhas do arquivo do certificado!" );

    }

  }

}