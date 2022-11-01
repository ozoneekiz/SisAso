<?php
require_once "../../config.php";
require_once "../../controladores/reciborecaudos.controlador.php";
require_once "../../modelos/reciborecaudos.modelo.php";
require_once "../../modelos/conexion.php";

class Recibo{
    
    public function getFilas($srtbusqueda,$tiporecibo){
        
    $datosrecibo = ReciboControlador::crtgetDatosRecibo($srtbusqueda,$tiporecibo);
    $nfilas=count($datosrecibo);
    return $nfilas;
       
    }
    public function getPlantilla($srtbusqueda,$tiporecibo){
        
        $datosrecibo = ReciboControlador::crtgetDatosRecibo($srtbusqueda,$tiporecibo);
        $institucion = ReciboModelo::mdl_GET_Institucion();
       
        $plantilla ='<div class="content">
                <div id="header">
                    <div class="logo"><img src="../../archivos/'; $plantilla.=$institucion[0]["logotipo"] ; $plantilla .='" width="120px" heigth="120px"></div>
                    <div class="nombreAsociacion">'; $plantilla.=$institucion[0]["razonSocial"] ; $plantilla .='</div>
                    <div class="direccion">RUC: '; $plantilla.=$institucion[0]["ruc"] ; $plantilla .='</div>
                    <div class="direccion">'; $plantilla.=$institucion[0]["Direccion"] ; $plantilla .='</div>
                    <div class="datosRecibo">
                        <span class="labelDato">N°recibo: &nbsp;</span><span  class="nserie">'; $plantilla.=$datosrecibo[0]["serie"] ; $plantilla .='</span>-<span  class="nrecibo">'; $plantilla.=$datosrecibo[0]["Nrecibo"] ; $plantilla .='</span><br>
                        <span class="labelDato">Fecha: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span  class="fecha">'; $plantilla.=$datosrecibo[0]["Fecha"] ; $plantilla .='</span><br>
                        <span class="labelDato">';
                        
                        if($tiporecibo=='alquiler' ){
                            $plantilla .='Usuario';
                        }else{
                            $plantilla .='Asociado'; 
                        }
                        
                        $plantilla .=': &nbsp;</span><span class="nAsociado">'; $plantilla.=$datosrecibo[0]["Nasociado"] ; $plantilla .='</span> <br>';
                        
                        if ($tiporecibo=="alquiler") {
                            $plantilla .='<span class="labelDato">Pago Por:&nbsp; </span><span  class="npagador">Alquiler</span><br>';
                        }
                        else if($datosrecibo[0]["Pagador"]!="Propietario" && ($tiporecibo=="xnpa" || $tiporecibo=="xstand")){
                            $plantilla .='<span class="labelDato">Inquilino:&nbsp; </span><span  class="npagador">'; $plantilla.=$datosrecibo[0]["Pagador"] ; $plantilla .='</span><br>';
                        }
                        
                        $plantilla.='        
                </div>   
                </div>
                <div id="body">
                    <table class="table" cellpadding="0" cellspacing="0">
                        <thead >
                            <tr>';
                            if ($tiporecibo=="alquiler") {
                                $plantilla .='<th class="stand">Contrato</th>
                                <th class="concepto">Recaudo</th>
                                <th class="precio">Aporte</th>';
                            } else if ($tiporecibo=="recaudo"){
                                $plantilla .='<th class="stand">stand</th>
                                <th class="concepto">Recaudo</th>
                                <th class="precio">Aporte</th>';
                            }  
                              else if ($tiporecibo=="servicios"){
                                $plantilla .='<th class="stand">cant.</th>
                                <th class="concepto">descripción</th>
                                <th class="precio">total</th>';
                            }
                            
                            
                            $plantilla .='</tr>
                            </thead>
                            <tbody class="cuerpo">
                                ';
                                // para tr stand si es servicio viene la cantidad desde el prc
                                foreach ($datosrecibo as $dr) {
                                    $plantilla.='<tr><td class="stand">'. $dr["Nstand"].'&nbsp;</td>';
                                    $plantilla.='<td class="concepto">'. $dr["Nrecaudo"].'.</td>';
                                    $plantilla.='<td class="precio">'. number_format($dr["pagos"],2) .'</td></tr>';
                                }

                                $plantilla .=
                                '
                      
                            </tbody>
                    </table>
                    <div class="total">
                        <div >Total S/: <span class="totalprecio" >'; $plantilla.= number_format($datosrecibo[0]["Total"],2)  ; $plantilla .='</span> </div>
                    </div>
                </div>
                <div id="footer">
                   <div class="mensajeFooter1">'; $plantilla.=$institucion[0]["notas"] ; $plantilla .='</div>
                   <div class="mensajeFooter1">'; $plantilla.=$institucion[0]["notas2"] ; $plantilla .='</div>
                </div>
            </div>
            ';
            return $plantilla;
          
        
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/r.css">
    <style>
        #recibos{
            width:240px;

        }
    </style>
</head>

<body>
    <div id="recibo">
    <?php
    $plantilla = new Recibo();
    $plantilla = $plantilla -> getPlantilla("22","recaudo");
    echo $plantilla;
    ?>
    </div>

    <button name="button" id="imprimir">Click me</button>

</body>

</html>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="printThis.js"></script>
<script>
    $("#imprimir").on("click", function () {
         $('#recibo').printThis({
            //importCSS: false,
            base:"sg",
            //debug: true,
            //importStyle:false,
            //importCSS:false,
            loadCSS:"css/r.css"
            //header: "<h1>Look at all of my kitties!</h1>",
         });   
    });


   
</script>
