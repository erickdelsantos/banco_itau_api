<?php
require_once('BancoContaItauApi.php');

class PixItau {

  private $api;
  private $txid;
/*
  private $e2eid;
  private $tipo;
  private $chave;
  private $revisao;
  private $data_vencimento;
  private $validade_apos_vencimento;
  private $valor;
  private $multa_tipo;
  private $multa_valor;
  private $desconto_tipo;
  private $desconto_valor;
  private $desconto_data;
  private $mensagem_para_cliente;
  private $devedor_tipo;
  private $devedor_documento;
  private $devedor_nome;
  private $devedor_email;
  private $devedor_cep;
  private $devedor_logradouro;
  private $devedor_cidade;
  private $devedor_uf;
  private $pagamento_valor;
  private $pagamento_data_hora;
  private $pagamento_chave;
  private $pagamento_mensagem;

*/

  public function __construct( $banco_itau_api ) {

    $this->api = $banco_itau_api; // Receives instance an instance of BancoItauApi

  }


  private function setTxId( $txid ) {

    if ( empty( $txid ) ) {

      throw new Exception( 'O TxId não foi informado!' );

    } else if ( strlen( $txid ) < 26 || strlen( $txid ) > 35 ) {
    
      throw new Exception( 'O tamanho do TxId informado está fora do padrão!' );
    
    }

    $this->txid = $txid;

  }


  public function setComVencimento( array $entrada ) {
    // array $entrada deve conter:
    // - txid[OBR]: Identificação única da cobrança PIX para a empresa
    // - json[OBR]: Dados da cobrança PIX no formato JSON

    $this->setTxId( $entrada['txid'] );

    if ( empty( $entrada['json'] ) ) {
    
      throw new Exception( 'Os dados da cobrança PIX em JSON não foram passados!' );
    
    }


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/cobv/'.$this->txid);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->api->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->api->key_path);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $entrada['json']);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->api->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->api->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 201 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar criar o PIX com vencimento no Itaú.<br><small>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ).'</small>' );
      
    } else {

      $original_output = utf8_decode( $output );

      $output = json_decode( $output );
      

      return array(
        'revisao' => $output->revisao,
        'status' => $output->status,
        'json_output' => $original_output
      );

    }
  
  }


  public function setUpdateComVencimento( array $entrada ) {
    // array $entrada deve conter:
    // - txid[OBR]: Identificação única da cobrança PIX para a empresa
    // - json[OBR]: Dados da cobrança PIX no formato JSON

    $this->setTxId( $entrada['txid'] );

    if ( empty( $entrada['json'] ) ) {
    
      throw new Exception( 'Os dados da cobrança PIX em JSON não foram passados!' );
    
    }


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/cobv/'.$this->txid);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->api->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->api->key_path);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $entrada['json']);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->api->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->api->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      $d = NULL;
      foreach ( $output->violacoes as $v ) {
      
        if ( !empty( $d ) ) { $d .= '<br>'; }

        $d .= '<u>Propriedade</u>: '.utf8_decode($v->propriedade).'<br>';
        $d .= '<u>Motivo</u>: '.utf8_decode($v->razao).'<br>';
        $d .= '<u>Valor</u>: '.utf8_decode($v->valor).'<br>';
      
      }

      throw new Exception( 'Houve um problema ao tentar modificar a cobrança PIX com vencimento no Itaú.<br><small>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ).'<br><b>Problemas:</b><br>'.$d.'</small>' );
      
    } else {

      $original_output = $output;

      $output = json_decode( $output );
      

      return array(
        'revisao' => $output->revisao,
        'status' => $output->status,
        'json_output' => $original_output
      );

    }
  
  }


  public function getComVencimento( $txid ) {
    // $txid[OBR]: Identificação única da cobrança PIX para a empresa

    $this->setTxId( $txid );


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/cobv/'.$this->txid);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->key_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->api->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->api->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar consultar os dados da cobrança PIX com vencimento no Itaú.<br><small>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ).'</small>' );
      
    } else {

      $output = json_decode( $output );

      return $output;

    }
  
  }


  public function getEmvComVencimento( $txid ) {
    // $txid: Identificação única da cobrança PIX para a empresa
    // Retorna um objetos com as seguintes propriedades:
    // emv: Código PIX copia e cola
    // imagem_base64: Imagem do QRCode codificada em Base64

    $this->setTxId( $txid );


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/cobv/'.$this->txid.'/qrcode');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->api->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->api->key_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->api->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->api->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar criar o PIX com vencimento no Itaú.<br><small>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ).'.' );
      
    } else {

      return json_decode( $output );
      
    }
  
  }


  public function getListaComVencimento( array $entrada ) {
    // $entrada, array com:
    // - data_inicial [OBR]: data no formato yyyy-mm-ddThh:mm:ss
    // - data_final [OBR]: data no formato yyyy-mm-ddThh:mm:ss
    // - status [OPC]: status, ATIVA, CONCLUIDA, REMOVIDA_PELO_PSP, REMOVIDA_PELO_USUARIO_RECEBEDOR
    // - page [OPC]: quantidade de itens or página
    // - qty_per_page [OPC]: quantidade de itens or página

    $query_params = '?inicio='.$entrada['data_inicial'].'&fim='.$entrada['data_final'];

    if ( !empty( $entrada['status'] ) ) {
    
      $query_params .= '&status='.$entrada['status'];
    
    }

    if ( !empty( $entrada['page'] ) ) {
    
      $query_params .= '&paginacao.paginaAtual='.$entrada['page'];
    
    }

    if ( !empty( $entrada['qty_per_page'] ) ) {
    
      $query_params .= '&paginacao.itensPorPagina='.$entrada['qty_per_page'];
    
    }


    $ch = curl_init('https://secure.api.itau/pix_recebimentos/v2/cobv'.$query_params);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSLCERT, $this->api->crt_path);
    curl_setopt($ch, CURLOPT_SSLKEY, $this->api->kay_path);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$this->api->access_token,
        'Cache-Control: no-cache',
        'Content-Type: application/json',
        'x-itau-apikey: '.$this->api->client_id
      )
    );

    $output = curl_exec($ch);

    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ( $http_status_code != 200 ) {

      $output = json_decode( $output );

      throw new Exception( 'Houve um problema ao tentar consultar os dados da cobrança PIX com vencimento no Itaú.<br><small>Código: '.$http_status_code.'<br>Título: '.utf8_decode( $output->title ).'<br>Detalhe: '.utf8_decode( $output->detail ).'</small>' );
      
    } else {

      $output = json_decode( $output );

      return $output;

    }
  
  }

}