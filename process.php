<?php 
session_start();
header('Content-Type: text/html; charset=utf-8');
include "conection.php";
include "utilitarios.php";

$nombre_fichero = '../files/midtid.txt';
$existe =  true;
$vacio = true;
$mid = "";
$tid = "";
$linea = "";

if (!file_exists($nombre_fichero)) {
    $existe=false;
} 
if (filesize($nombre_fichero) == 0){
  $vacio = false;
}else{
  $file = fopen($nombre_fichero, "r") or exit("Error abriendo fichero!");
  $linea = fgets($file);
  $terminal =  explode("_", $linea);
  $mid = trim($terminal[0]);
  $tid = trim($terminal[1]);

  fclose($file);     
}



$merchantId=$mid;//Homologación 7100040113
$terminalId=$tid;//BP para OTT

/*
$merchantId="5000004001";//SUPERMAXI
$terminalId="L0100402";

*/

/*$merchantId="1000000505";
$terminalId="PD100406";*/






$_SESSION['merchterm'] = $merchterm;


/*
**Low Risk - DATAFAST*/
/*if($modalidad=='1'){
	$_SESSION['entityId'] = "8a8294185a65bf5e015a6c8b89a10d8d";
	$_SESSION['autorizador'] =  "OGE4Mjk0MTg1YTY1YmY1ZTAxNWE2YzhiMmY2OTBkOGJ8UmtqcHlOTkU4cw==";
}else{*/
	$_SESSION['entityId'] = "8a8294175f113aad015f11652f2200a5";
	$_SESSION['autorizador'] =  "OGE4Mjk0MTg1YTY1YmY1ZTAxNWE2YzhjNzI4YzBkOTV8YmZxR3F3UTMyWA==";
//}

//$_SESSION['entityId']=$entity;
//$_SESSION['autorizador']=$token;
/*$_SESSION['userId']="8a8294185a65bf5e015a6c8b2f690d8b";
$_SESSION['password']="RkjpyNNE8s";*/









function request($items, $total,$iva,$totaTarifa12,$totalBase0,$email, $primer_nombre, $segundo_nombre, $apellido, $cedula, $trx,$ip_address, $finger,$merchterm,
	$telefono, $direccion_cliente, $pais_cliente, $direccion_entrega, $pais_entrega) {
	$finger = urlencode($finger);
	$i = 0;
	$url = "https://test.oppwa.com/v1/checkouts";
	$iva 			=  str_replace('.', '', $iva); 
	$totaTarifa12 	=  str_replace('.', '', $totaTarifa12); 
	$totalBase0 		=  str_replace('.', '', $totalBase0); 
	$valueIva 		= str_pad($iva, 12, '0', STR_PAD_LEFT);
	$valueTotalIva 	= str_pad($totaTarifa12, 12, '0', STR_PAD_LEFT);
	$valueTotalBase0= str_pad($totalBase0, 12, '0', STR_PAD_LEFT);	
	$data = "entityId=".$_SESSION['entityId'].
		"&amount=".$total.
		"&currency=USD".
		"&paymentType=DB".
		"&customer.givenName=".$primer_nombre.
		"&customer.middleName=".$segundo_nombre.
		"&customer.surname=".$apellido.
		"&customer.ip=".$ip_address.
		"&customer.merchantCustomerId=000000000001".
		"&merchantTransactionId=transaction_".$trx.		
		"&customer.email=".$email.
		"&customer.identificationDocType=IDCARD".		
		"&customer.identificationDocId=".$cedula.
		"&customer.phone=".$telefono.
		"&billing.street1=".$direccion_cliente.
		"&billing.country=".$pais_cliente.
		"&shipping.street1=".$direccion_entrega.
		"&shipping.country=".$pais_entrega.
		/*"&recurringType=INITIAL".*/
		"&risk.parameters[USER_DATA2]=DATAFAST".
		"&customParameters[SHOPPER_VERSIONDF]=2".
		"&customParameters[".$merchterm."]=00810030070103910004012".$valueIva."05100817913101052012".$valueTotalBase0."053012".$valueTotalIva;
		
	foreach ($items["cart"] as $c) {
		
		$data.= "&cart.items[".$i."].name=".$c["product_name"];
		$data.= "&cart.items[".$i."].description="."Descripcion: ".$c["product_name"];
		$data.= "&cart.items[".$i."].price=".$c["product_price"];
		$data.= "&cart.items[".$i."].quantity=".$c["q"];		
		$i++;
	}
	
	$data .="&testMode=EXTERNAL";	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization:Bearer '.$_SESSION['autorizador']));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$responseData = curl_exec($ch);
	if(curl_errno($ch)) {
		return curl_error($ch);
	}
	curl_close($ch);
	return $responseData;
}

$baseUrl = "https://pagostest.datafast.com.ec/df/payment.php";

if(!is_float($totalBaseIva))
	$totalBaseIva= number_format((float)$totalBaseIva, 2, '.', '');

if(!is_float($totalBase0))
	$totalBase0 = number_format((float)$totalBase0, 2, '.', '');

$iva =  $totalBaseIva * 0.12;
$iva =  round($iva,2);
$iva = number_format((float)$iva, 2, '.', '');

$total = $totalBaseIva + $iva + $totalBase0; //Monto total de la transaccion
$total = number_format((float)$total, 2, '.', '');


$responseData = request($items_details, $total,$iva,$totalBaseIva,$totalBase0, $email, $primer_nombre, $segundo_nombre, $apellido,$cedula, $trx, $ip_address, $finger,$merchterm, $telefono, $direccion_cliente, $pais_cliente, $direccion_entrega, $pais_entrega);
$json = json_decode($responseData, true);

?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="../bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="../card.min.css">
</head>
<script type='text/javascript' src="../jquery-3.2.1.js"></script>
<script type='text/javascript' src="../bootstrap.min.js"></script>

<body class="well">
<script src="https://test.oppwa.com/v1/paymentWidgets.js?checkoutId=<?php echo $json['id'] ?>"></script>


<div class="container">
	<div class="row">
		<div class="col-md-12">
			<img src="../imagenes/logo-datafast.png">
		</div>
		<div class="col-md-12">
		<h1>Portal de compras</h1>
					<nav class="navbar navbar-default">
					  <div class="container-fluid">
					    <div class="navbar-header">
					      <a class="navbar-brand active" href="#">Datafast S.A.</a>
					    </div>
					    <ul class="nav navbar-nav" >					      
					      <li><a href="../cart.php">Carrito de Compras</a></li>					      					      
					    </ul>
					  </div>
					</nav>
					<br><br><hr>
			
		</div>
		<div style="display:block; width: 100%;">
			<form style="display: inline-block;" action="<?php echo $baseUrl ?>" class="paymentWidgets" data-brands="VISA MASTER DINERS DISCOVER AMEX ALIA">	
			</form>
		</div>
		<div class="row">
		<div class="col-md-12 text-center">
		<!-- <img src="../imagenes/marcas.png"> -->
		</div>
	</div>
	<p>Powered by <a href="http://www.datafast.com.ec/" target="_blank">Datafast</a></p>	
	</div>
</div>
</body>

<script type="text/javascript">

$(document).ready(function(){
var myVar = setInterval(myTimer ,3000);
function myTimer() {
	var frame = $("iframe.wpwl-target");
	if(frame.css("display") === "inline"){
		frame.css("display","block");
		window.clearInterval(myVar);
	}
}
});

  	var wpwlOptions = {
  	  onReady: function() {

			var numberOfInstallmentsHtml = '<div class="wpwl-label wpwl-label-custom" style="display:inline-block">Diferidos:</div>' +
              '<div class="wpwl-wrapper wpwl-wrapper-custom" style="display:inline-block">' +
              '<select name="recurring.numberOfInstallments"><option value="0">0</option><option value="3">3</option><option value="6">6</option><option value="9">9</option></select>' +
              '</div>'; 
            $('form.wpwl-form-card').find('.wpwl-button').before(numberOfInstallmentsHtml);
            var frecuente = 
              '<div class="wpwl-wrapper wpwl-wrapper-custom" style="display:inline-block">' +
              'Tipo de crédito:<select name="customParameters[SHOPPER_TIPOCREDITO]"><option value="00">Corriente</option>'+
			  '<option value="01">Dif Corriente</option>' +
			  '<option value="02">Dif con int</option>' +
			  '<option value="03">Dif sin int</option>' +
			  '<option value="07">Dif con int + Meses gracia</option>' +
			  '<option value="09">Dif sin int + Meses gracia</option>' +
			  '<option value="21">Dif plus cuotas</option>' +
			  '<option value="22">Dif plus</option>' +
              '</div>'; 
            $('form.wpwl-form-card').find('.wpwl-button').before(frecuente);

			

            /*var gracia = '<div class="wpwl-label wpwl-label-custom" style="display:inline-block">Meses de Gracia:</div>' +
              '<div class="wpwl-wrapper wpwl-wrapper-custom" style="display:inline-block">' +
              '<select name="customParameters[SHOPPER_gracia]"><option value="0">No</option><option value="1">Si</option></select>' +
              '</div>'; 
            $('form.wpwl-form-card').find('.wpwl-button').before(gracia);*/
  	  		
            /*var tipoCredito = '<div class="wpwl-wrapper wpwl-wrapper-custom" style="display:inline-block">' +
              '<input type="hidden" name="customParameters[SHOPPER_tipoCredito]" value="01">' +
              '</div>'; 
            $('form.wpwl-form-card').find('.wpwl-button').before(tipoCredito);*/
            var datafast= '<br/><br/><img src='+'"https://www.datafast.com.ec/images/verified.png" style='+'"display:block;margin:0 auto; width:100%;">';
			$('form.wpwl-form-card').find('.wpwl-button').before(datafast);

			/** 
				var regresar = 
              '<div class="wpwl-wrapper wpwl-wrapper-custom" style="display:inline-block">' +
              '<button type="button" onclick="history.back()" class="btn btn-success" >Regresar</button>' +
              '</div>'; 
            $('form.wpwl-form-card').find('.wpwl-button').before(regresar);
			
			**/
			        
          },              
          style: "card",
          locale: "es",                      
          labels: {cvv: "CVV", cardHolder: "Nombre(Igual que en la tarjeta)", insertCode:"Ingrese el codigo"},
		  brandDetectionPriority: ["ALIA", "VISA"],/*
		  onDetectBrand: function(brands){
				
      }
</script>




</html>
