<?php
require_once "conexion.php";
require_once "recaudos.modelo.php";


class EventosModelo
{


    static public function mdl_CREATE_Evento($tipo, $fechahora, $nombre, $descripcion, $convocados, $multafalta, $multatarde, $estado, $grupo)
    {
        /**se borro casa inicio  
        $npa_asociados = EventosModelo::mdl_GET_npa_para_create();

        if ($grupo == "algunos") {
            /  validamos la variable afectos y la formateamos 
            $convocados = EventosModelo::expresionNrosToarray($convocados);
            $convocados = array_unique($convocados);

            $rhabidos = array_intersect($convocados, $npa_asociados);
            $rnohabidos = array_unique(array_diff($convocados, $npa_asociados));


            // volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $nconvocados = EventosModelo::ArrayToExpresionNros($rhabidos);
        }

        if ($grupo == "todos") {

            $rhabidos = $npa_asociados;
            // volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $nconvocados = "Todos los asociados activos";
        } 
        */
        /**se borro casa fin */

        /** cambios inicio */
        $slcnpas = RecaudosModelo::mdl_GET_npas_para_create();
        
        $arrID_asocaciado = array_column($slcnpas, 'id');
        $arrnpas = array_column($slcnpas, 'npa');
        
       /*  $respuesta["arr_id_asciados"]=$arr_ids_asociados;
        $respuesta["slcstands"]=$slcnpas;
        $respuesta["aataArr"]=$DataArr; */
        //return $respuesta;

        if ($grupo == "algunos") {
            $arrafectos = RecaudosModelo::expresionNrosToarray($convocados);
            $arrafectos = array_unique($arrafectos);
            
            $arr_NPAs_habidos = array_intersect($arrafectos, $arrnpas);
            $rnohabidos = array_unique(array_diff($arrafectos, $arrnpas));

            $cantidadafectos = count($arr_NPAs_habidos);
            $afectosExpre = RecaudosModelo::ArrayToexpresionNros($arr_NPAs_habidos);

              /** para autovaluo esta en otra forma pero mismo resultado */
            foreach($arr_NPAs_habidos as $nropadron){
                $clave = array_search($nropadron, array_column($slcnpas, 'npa'));
                $arr_ids_asociados[]=$slcnpas[$clave]['id'];
            }

        }
        

        if ($grupo == "todos") {
            $arr_ids_asociados = $arrID_asocaciado;
            $cantidadafectos = count($arr_ids_asociados);
            $afectosExpre = "Todos los números de padron activos";
        }
        /** cambios FIN */

        /*** FIN: validamos la variable afectos y la formateamos  ***/

        if (count($arr_ids_asociados ) == 0) {
            $respuesta[0] =  'error';
            $respuesta[1] = 'Ningun stand Afecto existe, el recaudo no se guardó';
            return $respuesta;
        }

        $fecha = date("Y-m-d H:i:s");
        // Cargamos la conexion en una variable para usar luego con lastInsert()
        $con = Conexion::conectar();
        $stmt = $con->prepare("INSERT INTO eventos (tipo,nombre,fechahoraevento,descripcion,convocados,montomultafalta,montomultatarde,estado,fechacreacion)
                                                                                values(:tipo,
                                                                                :nombre,
                                                                                :fechahoraevento,
                                                                                :descripcion,
                                                                                :convocados,
                                                                                :montomultafalta,
                                                                                :montomultatarde,
                                                                                :estado,
                                                                                :fechacreacion)");
        $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":fechahoraevento", $fechahora, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":convocados", $afectosExpre , PDO::PARAM_STR);
        $stmt->bindParam(":montomultafalta", $multafalta, PDO::PARAM_INT);
        $stmt->bindParam(":montomultatarde", $multatarde, PDO::PARAM_INT);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindParam(":fechacreacion", $fecha, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $respuesta[0] = "error";
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = "no se pudo crear el evento";
            return $respuesta;
        }
        ///para usar  le metodo lastinsert() debe ser pormedio0 de la misma conexion  -->$con

        $id = $con->lastInsertId();
        
       

        // construimos los values para la query
        $DataArr = array();
        foreach ($arr_ids_asociados as $id_asociado) {
            $DataArr[] = "('$id_asociado','$id','convocado')";
        }
        $values = implode(",", $DataArr);


        $stmt = Conexion::conectar()->prepare("INSERT INTO asistencia(id_asociado,
                                                                             idevento,
                                                                             estado)
                                                        VALUES {$values}");


        if (!$stmt->execute()) {
            $respuesta[0] =  'error';
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = "no se pudo guardar la lista de convocados";
            return $respuesta;
        }

        if ($grupo == "algunos") {
            if (count($rnohabidos) > 0) {
                $rnohabidos = EventosModelo::ArrayToexpresionNros($rnohabidos);
                $rnohabidos = explode(",", $rnohabidos);
                $respuesta["no_habidos"] = [$rnohabidos];
            }
        }
        $respuesta[0] = 'ok';
        $respuesta[1] = "El evento {$nombre} se registró correctamente <br>Tambien se crearon las convocatorias a los asociados";
        
       
        
        return $respuesta;
    }
    static public function mdl_GET_Eventos($idevento, $estado)
    {

        $estados = explode(",", $estado);
        $respuesta = [];

        foreach ($estados as $fila) {
            $stmt = Conexion::conectar()->prepare('call prc_ListarEventos(?,?)');
            $stmt->bindParam("1", $idevento, PDO::PARAM_INT, 8);
            $stmt->bindParam("2", $fila, PDO::PARAM_STR, 12);
            $stmt->execute();
            $resultado = $stmt->fetchAll();
            $respuesta = array_merge($respuesta, $resultado);
        }
        return $respuesta;
    }
    static public function mdl_GET_Asistentes($idevento, $estado)
    {

        $stmt = Conexion::conectar()->prepare('call prc_ListarAsistentes(?,?)');
        $stmt->bindParam("1", $idevento, PDO::PARAM_STR, 8);
        $stmt->bindParam("2", $estado, PDO::PARAM_STR, 8);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    static public function mdl_UPDATE_evento($id, $fechahora, $nombre, $descripcion)
    {

        $stmt = Conexion::conectar()->prepare('UPDATE eventos SET   nombre=:nombre,
                                                                    fechahoraevento=:fechahoraevento,
                                                                    descripcion=:descripcion
                                                                    WHERE id =:id');
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":fechahoraevento", $fechahora, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);


        if ($stmt->execute()) {
            $respuesta[0] = 'Actualizado';
            $respuesta[1] = " Se actualizó correctamente";
            $respuesta[2] = $id;
        } else {
            $respuesta = $stmt->errorInfo();
        }

        return $respuesta;
    }

    static public function mdl_DELETE_evento($id)
    {
        // comporbamos si existe pagos echos para evitar la eliminacion

        $stmt = Conexion::conectar()->prepare("SELECT * FROM asistencia WHERE idevento=:id AND estado='asistio'");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $asistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($asistentes) > 0) {
            $respuesta[0] = 'enuso';
            $respuesta[1] = "Ya existen  " . count($asistentes) . " asistentes al evento";
            //$respuesta[2] = $asistentes;
            return $respuesta;
        }


        $stmt = Conexion::conectar()->prepare('DELETE FROM Eventos where id=:id');
        $stmt->bindParam(":id", $id, PDO::PARAM_INT, 5);

        if ($stmt->execute()) {
            $respuesta[0] = ['ok'];
            $respuesta[1] = " se Eliminó correctamente";
        } else {
            $respuesta = $stmt->errorInfo();
        }

        return $respuesta;
    }
    /* static public function mdl_GET_npa_para_create()
    {

        $stmt = Conexion::conectar()->prepare('SELECT cast(numerodepadron as int) as numerodepadron FROM asociados WHERE estado="activo" ORDER BY numerodepadron ASC');
        $stmt->execute();
        $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $npaAsocadidos = array_column($stmt, 'numerodepadron');
        return $npaAsocadidos;
    } */
    static public function mdl_Abrir_Cerrar_evento($id, $estado)
    {
        // cambiamos el estado a l evento
        $stmt = Conexion::conectar()->prepare('UPDATE eventos SET estado=:estado where id=:id');
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR, 10);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT, 5);

        if (!$stmt->execute()) {

            $respuesta[0] = ['error'];
            $respuesta[1] = "No se pudo cambiar el estado del evento a " . $estado;
            $respuesta[2] =  $stmt->errorInfo();
            return $respuesta;
        }

        $datosevento = EventosModelo::mdl_GET_eventos($id, $estado);
        $tipodeevento = $datosevento[0][1];



        if ($estado == "terminado") {

            $arr_asistentes = EventosModelo::mdl_GET_Asistentes($id, "asistio");
            $arr_asistentes_tarde = EventosModelo::mdl_GET_Asistentes($id, "tardanza");
            $arr_no_asistentes = EventosModelo::mdl_GET_Asistentes($id, "convocado");




            //  cargo en un array los npa de los no asistentes [1]
            foreach ($arr_no_asistentes as $key => $fila) {
                $afectosMultaArr[$key] = intval($fila[1]);
            }



            if ($tipodeevento == "Reparto") {

                $respuesta[0] = 'ok';
                $respuesta[1] = "El evento cambio de estado a: " . $estado . "<br>Asistieron: " . count($arr_asistentes);
            } else {

                // creamos el recaudos_por_npa y los pago_por_npa si es que los no asistenten son mayores a cero
                if (count($afectosMultaArr) > 0) {

                    $nombre = "Multa falta " . $datosevento[0][2] . " " . substr($datosevento[0][3], 0, 11);
                    $precio = $datosevento[0][7];
                    $descripcion = "multa por inasistencia a la " . $datosevento[0][2] . " del dia" . $datosevento[0][3];

                    $afectos = implode(",", $afectosMultaArr);

                    $fecha_actual = date("Y-m-d");
                    $fechav = date("Y-m-d", strtotime($fecha_actual . "+ 1 month"));
                    $grupo="algunos";

                    $respuestapagos = recaudosModelo::mdl_CREATE_Recaudo_por_npa($nombre, $precio, $descripcion, $afectos, $fechav,$grupo);

                    $sumaasistentes = count($arr_asistentes) + count($arr_asistentes_tarde);
                    $respuesta[0] = 'ok';
                    $respuesta[1] = "Asistieron: " . $sumaasistentes . " Asociados <br>
                                    llegaron tarde: " . count($arr_asistentes_tarde) . " Asociados<br>
                                   faltaron: " . count($arr_no_asistentes) . " Asociados<br>
                                   Se crearon las multas por falta a los inasistentes";


                    // actualizamos el estado del convocado a falta 
                    foreach ($afectosMultaArr as $npa) {
                        $stmt = Conexion::conectar()->prepare('UPDATE asistencia SET estado=:estado WHERE idevento=:idevento AND numerodepadron=:npa');
                        $stmt->bindParam(":idevento", $id, PDO::PARAM_INT, 8);
                        $stmt->bindParam(":npa", $npa, PDO::PARAM_INT, 8);
                        $estadoasistencia = "falta";
                        $stmt->bindParam(":estado", $estadoasistencia, PDO::PARAM_STR);
                        $stmt->execute();
                    }
                    $respuesta[9] = "Se cambio de estado a los que faltaron";
                }

                if (count($arr_asistentes_tarde) > 0) {

                    $nombre = "Multa tardanza " . $datosevento[0][2] . " " . substr($datosevento[0][3], 0, 11);
                    $precio = $datosevento[0][8];
                    $descripcion = "multa por tardanza a la " . $datosevento[0][2] . " del dia" . $datosevento[0][3];

                    //listado de npa 
                    foreach ($arr_asistentes_tarde as $key => $fila) {
                        $afectosMultaTardeArr[$key] =  intval($fila[1]);
                    }
                    $afectos = implode(",", $afectosMultaTardeArr);
                    $fecha_actual = date("Y-m-d");
                    $fechav = date("Y-m-d", strtotime($fecha_actual . "+ 3 day"));
                    $grupo="algunos";

                    $respuestapagostardanza = recaudosModelo::mdl_CREATE_Recaudo_por_npa($nombre, $precio, $descripcion, $afectos, $fechav,$grupo);


                    $respuesta[7] = "<br>Asistieron: " . count($arr_asistentes_tarde) . " Tarde <br>Se crearon las multas por tardanza";
                    $respuesta[8] = $respuestapagostardanza;
                }

                if (count($arr_asistentes_tarde) == 0 && count($arr_asistentes) == 0) {

                    $respuesta[0] = 'ok';
                    $respuesta[1] = "El evento cambio de estado a: " . $estado . "<br>No vino nadie<br>Se crearon las multas a todos";
                }
            }


            // guardamos la fecha y hora de cierre
            $stmt = Conexion::conectar()->prepare('UPDATE eventos SET fechacierre=:fechacierre where id=:id');
            $fecha = date("Y-m-d H:i:s");
            $stmt->bindParam(":fechacierre", $fecha, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT, 5);
            $stmt->execute();

            $respuesta[9] = "Se guardo la fecha de cierre";
        } elseif ($estado == "abierto") {
            // guardamos la fecha de apertura si abrimos el evento con estado abierto
            $stmt = Conexion::conectar()->prepare('UPDATE eventos SET fechaapertura=:fechaapertura where id=:id');
            $fecha = date("Y-m-d H:i:s");
            $stmt->bindParam(":fechaapertura", $fecha, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT, 5);
            $stmt->execute();

            $respuesta[0] = "ok";
            $respuesta[1] = "Se abrio el evento";
        }
        /* 
        $respuesta["d_afectos_multa"] = $afectosMultaArr;
        $respuesta["d_NRO_afectos_multa"] = count($afectosMultaArr);
        $respuesta["e_afectos_implode"]= $afectos;
        $respuesta["f_afectos_arra"]= $arrafectos;
        $respuesta["a_asistentes"] = $arr_asistentes;
        $respuesta["a_nro_asistentes_suma"] = count($arr_asistentes)+count($arr_asistentes_tarde);
        $respuesta["b_tarde"] = $arr_asistentes_tarde;
        $respuesta["b_nro_tarde"] = count($arr_asistentes_tarde);
        $respuesta["c_NO_asistentes"] = $arr_no_asistentes;
        $respuesta["c_nro_NO_asistentes"] = count($arr_no_asistentes);               
        $respuesta["respuesta pago"] = $respuestapagos; */
        return $respuesta;
    }
    static public function mdl_Marcar_Asistencia($idevento, $npa)
    {

        $stmt = Conexion::conectar()->prepare('SELECT a.estado, aso.id as id_asociado FROM asistencia a
                                                INNER JOIN asociados aso ON aso.id = a.id_asociado
                                                where (cast(aso.numerodepadron as int)=:npa or aso.numerodepadron=:npa)AND a.idevento=:idevento');
        $stmt->bindParam(":npa", $npa, PDO::PARAM_STR, 8);
        $stmt->bindParam(":idevento", $idevento, PDO::PARAM_INT, 8);
        $stmt->execute();
        $resultado = $stmt->fetchAll();

        $id_asociado= $resultado[0]["id_asociado"];

       /*  $respuesta[2] = $resultado;
        $respuesta["npa_ingresado"] = $npa;
        $respuesta["coun"] = count($resultado);
        return $respuesta; */
        
        if (count($resultado) == 0) {
            $respuesta[0] = 'error';
            $respuesta[1] = "Nro Padron inactivo";
            $respuesta[2] = $resultado;
            $respuesta["npa_ingresado"] = $npa;
            return $respuesta;
        }

        
        if ($resultado[0]["estado"] != "convocado") {
            $respuesta[0] = 'error';
            $respuesta[1] = "El Asociado ya registró su ingreso.";
            $respuesta[2] = $resultado[0]["estado"];
            return $respuesta;
        }

        $fecha = date("Y-m-d H:i:s");

        $stmt = Conexion::conectar()->prepare('SELECT tipo, fechahoraevento as fechahora FROM eventos where id=:idevento');
        $stmt->bindParam(":idevento", $idevento, PDO::PARAM_INT, 8);

        if (!$stmt->execute()) {

            $respuesta[0] = ['error'];
            $respuesta[1] = "No se pudo ver eventos";
            $respuesta[2] =  $stmt->errorInfo();
            return $respuesta;
        }
        $horaprogramada = $stmt->fetchAll();

        /** seteamos el estado de la asitencia */
        if ($horaprogramada[0]["fechahora"] > $fecha || $horaprogramada[0]["tipo"] == "Reparto") {
            $estado = "asistio";
        } else {
            $estado = "tardanza";
        }


        $stmt = Conexion::conectar()->prepare('UPDATE asistencia SET estado=:estado, fechahora_asistecia=:fecha where idevento=:idevento AND id_asociado=:id_asociado');
        $stmt->bindParam(":idevento", $idevento, PDO::PARAM_INT, 8);
        $stmt->bindParam(":id_asociado", $id_asociado, PDO::PARAM_INT, 8);
        $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);


        if ($stmt->execute()) {
            $stmt = null;

            $stmt = Conexion::conectar()->prepare("UPDATE eventos SET nroasistentes = nroasistentes + 1 WHERE id=:idevento");
            $stmt->bindParam(":idevento", $idevento, PDO::PARAM_INT, 8);


            if ($stmt->execute()) {

                $respuesta[0] = ['ok'];
                $respuesta[1] = "La asistencia se registro";
                $respuesta[2] =  $idevento;
            } else {
                $respuesta = $stmt->errorInfo();
            }
        } else {
            $respuesta = $stmt->errorInfo();
        }



        return $respuesta;
    }
    function expresionNrosToarray($expresion)
    {
        $row = (explode(",", $expresion));
        $arraypartA = [];
        $arraypartB = [];

        foreach ($row as $valor) :
            if (preg_match("/-/", $valor)) {

                $cotas[] = (explode("-", $valor))[0];
                $cotas[] = (explode("-", $valor))[1];
                asort($cotas);
                $cotas = array_values($cotas);
                $cotas1 = $cotas[0];
                $cotas2 = $cotas[1];

                for ($i = $cotas1; $i <= $cotas2; $i++) {
                    $arraypartA[] = strval($i);
                    //$af0[] = $i;
                }
            } else {
                $arraypartB[] = $valor;
            };
            unset($cotas);
        endforeach;

        $arrayTotal = array_merge($arraypartB, $arraypartA);
        asort($arrayTotal);
        return $arrayTotal;
    }

    function ArrayToexpresionNros($arraydenumeros)
    {

        asort($arraydenumeros);
        $arraydenumeros = array_values($arraydenumeros);
        $cota0[] = $arraydenumeros[0];
        (int)$primerNro = $arraydenumeros[0];

        foreach ($arraydenumeros as $nro) {
            if ($primerNro != $nro) {
                $diferencia = (int)$nro - $primerNro;
                $cota0[] = $nro;
                $cota1[] = $primerNro - 1;
                $primerNro = $primerNro + $diferencia + 1;
            } else {
                $primerNro = (int)$primerNro + 1;
            }
        }
        $cota1[] = $arraydenumeros[count($arraydenumeros) - 1];
        $size = count($cota0);
        $expresion = "";
        for ($i = 0; $i < $size; $i++) {
            $coma = (count($cota0) - 1 != $i) ? "," : "";
            if (((int)$cota0[$i]) + 1 == (int)$cota1[$i]) {
                $expresion .= $cota0[$i] . "," . $cota1[$i] . $coma;
            } else if ($cota0[$i] != $cota1[$i]) {
                $expresion .= $cota0[$i] . "-" . $cota1[$i] . $coma;
            } else {
                $expresion .= $cota0[$i] . $coma;
            }
        }
        return $expresion;
    }
}
//$ver =new EventosModelo();
//$ver->mdl_GET_stand_para_create();