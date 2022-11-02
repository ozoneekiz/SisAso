<?php
require_once "conexion.php";


class RecaudosModelo
{
        // crear recaudos por stand
    static public function mdl_CREATE_Recaudo($nombre, $precio, $descripcion, $afectos, $fechav, $grupo)
    {
        $slcstands = RecaudosModelo::mdl_GET_stand_para_create();
        $arrIDstands = array_column($slcstands, 'id');
        $arrNROstands = array_column($slcstands, 'nrostand');

        if ($grupo == "algunos") {


            /***  validamos la variable afectos y la formateamos  ***/
            $arrafectos = RecaudosModelo::expresionNrosToarray($afectos);
            $arrafectos = array_unique($arrafectos);
            $arr_Stand_habidos = array_intersect($arrafectos, $arrNROstands);
            

            /** para autovaluo esta en otra forma pero mismo resultado */
            foreach($arr_Stand_habidos as $nrodestand){
                $clave = array_search($nrodestand, array_column($slcstands, 'nrostand'));
                $arrhabidos[]=$slcstands[$clave]['id'];
            }
            
            $rnohabidos = array_unique(array_diff($arrafectos, $arrNROstands));
            $cantidadafectos = count($arrhabidos);

            //* volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $afectosExpre = RecaudosModelo::ArrayToExpresionNros($arr_Stand_habidos);
            /*** FIN: validamos la variable afectos y la formateamos  ***/
        }
        if ($grupo == "todos") {
            $arrhabidos = $arrIDstands;
            //* volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $afectosExpre = "Todos los stands activos";
            $cantidadafectos = count($arrIDstands);
        }

        if (count($arrhabidos) == 0) {
            $respuesta[0] =  ['error'];
            $respuesta[1] = ['Ningun stand Afecto existe, el recaudo no se guardó'];
            return $respuesta;
        }

        $fecha = date("Y-m-d H:i:s");

        // Cargamos la conexion en una variable para usar luego con lastInsert()
        $con = Conexion::conectar();

        $stmt = $con->prepare("INSERT INTO recaudos(nombre, precio, descripcion, afectos, fechadecreacion, fechadevencimiento,totalafectos)
                                                                                values(:nombre,
                                                                                :precio,
                                                                                :descripcion,
                                                                                :afectos,
                                                                                :fechadecreacion,
                                                                                :fechav,
                                                                                :totalafectos)");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":precio", $precio, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":afectos", $afectosExpre, PDO::PARAM_STR);
        $stmt->bindParam(":fechadecreacion", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":fechav", $fechav, PDO::PARAM_STR);
        $stmt->bindParam(":totalafectos", $cantidadafectos, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $respuesta[0] =  'error';
            $respuesta[1] = $stmt->errorInfo();
        }

        ///para usar  le metodo lastinsert() debe ser pormedio de la misma conexion  -->$con
        $id = $con->lastInsertId();

        // creamos los values para la query
        $DataArr = array();
        foreach ($arrhabidos as $idstand) {
            $DataArr[] = "('$idstand','$id','$precio','0')";
        }
        $values = implode(",", $DataArr);

        $stmt = Conexion::conectar()->prepare("INSERT INTO pagos(id_stand,idrecaudo,pago,estado)
                                                      VALUES {$values}");
        if (!$stmt->execute()) {
            $respuesta[0] =  'error';
            $respuesta[1] = $stmt->errorInfo();
        }

        if ($grupo == "algunos") {
            if (count($rnohabidos) > 0) {
                $rnohabidos = RecaudosModelo::ArrayToexpresionNros($rnohabidos);
                $rnohabidos = explode(",", $rnohabidos);
                $respuesta["no_habidos"] = $rnohabidos;
            }
        }

        $respuesta[0] = 'ok';
        $respuesta[1] = "el recaudo {$nombre} se Registró correctamente <br>
        Tambien se crearon Los pagos en estado: pendiente a todos stand activos";
        //$respuesta[2] = $values;

        return $respuesta;
    }
    static public function mdl_CREATE_Recaudo_autovaluo($nombre, $precio, $descripcion, $afectos, $fechav, $grupo)
    {
        $slcstands = RecaudosModelo::mdl_GET_stand_para_create();
        $arrIDstands = array_column($slcstands, 'id');
        $arrNROstands = array_column($slcstands, 'nrostand');
        
        if ($grupo == "algunos") {
            
            $arrafectos = RecaudosModelo::expresionNrosToarray($afectos);
            $arrafectos = array_unique($arrafectos);
            
            $arrhabidos = array_intersect($arrafectos,$arrNROstands);
            $rnohabidos = array_unique(array_diff($arrafectos, $arrNROstands));
            $cantidadafectos = count($arrhabidos);

            // le creamos in indice al arrary afectos para poder comparar los indices  con arra_uinsersect
            foreach ($arrafectos as $nrostand) {
                //$arrdimafectos[] = ["nrostand" => $nrostand, "nropisos" => "hola"];
                $arrafectos_CI[] = ["nrostand" => $nrostand];
            }

            // lo Intersecamos para optener solo los estan habidos la resultante es el primer array de la funcion con sus columas incluidas
            $arr_stand_habidos = array_uintersect($slcstands, $arrafectos_CI,  function ($val1, $val2) {
                return strcmp($val1['nrostand'], $val2['nrostand']);
            });

            /* foreach($arr_Stand_habidos as $nrodestand){
                $clave = array_search($nrodestand, array_column($slcstands, 'nrostand'));
                $arrhabidos[]=$slcstands[$clave]['id'];
            } */
                      
            //* volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $afectosExpre = RecaudosModelo::ArrayToExpresionNros($arrhabidos);
        }

        if ($grupo == "todos") {
            $arr_stand_habidos = $arrIDstands;
            //* volvemos a formatear en STRING la expresion de la susecion para ingresala a la db
            $afectosExpre = "Todos los stands activos";
            $cantidadafectos = count($slcstands);
        }



        if ($cantidadafectos == 0) {
            $respuesta[0] =  ['error'];
            $respuesta[1] = ['Ningun stand Afecto existe, el recaudo no se guardó'];
        }

        $fecha = date("Y-m-d H:i:s");
        // Cargamos la conexion en una variable para usar luego con lastInsert()
        $con = Conexion::conectar();
        $stmt = $con->prepare("INSERT INTO recaudos(nombre, precio, descripcion, afectos, fechadecreacion, fechadevencimiento,totalafectos)
                                                                                values(:nombre,
                                                                                :precio,
                                                                                :descripcion,
                                                                                :afectos,
                                                                                :fechadecreacion,
                                                                                :fechav,
                                                                                :totalafectos)");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":precio", $precio, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":afectos", $afectosExpre, PDO::PARAM_STR);
        $stmt->bindParam(":fechadecreacion", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":fechav", $fechav, PDO::PARAM_STR);
        $stmt->bindParam(":totalafectos", $cantidadafectos, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $respuesta[0] = "error";
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = 'error en insert recaudo autovaluo';
            return $respuesta;
        }
        ///para usar  le metodo lastinsert() debe ser pormedio de la misma conexion  -->$con
        $id = $con->lastInsertId();

        // creamos los values para la query
        $DataArr = array();

        foreach ($arr_stand_habidos as $filaStand) {

            $idstand = $filaStand['id'];
            $pago = $precio * intval($filaStand['nropisos']);

            $DataArr[] = "('$idstand','$id','$pago','0')";
        }
        $values = implode(",", $DataArr);


        $stmt = Conexion::conectar()->prepare("INSERT INTO pagos(id_stand,idrecaudo,pago,estado)
                                                        VALUES {$values}");


        // aqui mejorar la respuesta creando un contador al final validar el numero de ejeciones para mostar respuesta
        if (!$stmt->execute()) {
            $respuesta[0] = 'error';
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = 'error en insert pago auto valuo';
        }

        if ($grupo == "algunos") {
            if (count($rnohabidos) > 0) {
                $rnohabidos = RecaudosModelo::ArrayToexpresionNros($rnohabidos);
                $rnohabidos = explode(",", $rnohabidos);
                $respuesta["no_habidos"] = [$rnohabidos];
                $respuesta["afectos_expre"] = $afectosExpre;
                $respuesta["cant_afectos"] = $cantidadafectos;
                $respuesta["afectos_expre"] = $afectosExpre;
            }
        }
        $respuesta[0] = ['ok'];
        $respuesta[1] = [" Se Registró correctamente"];
        $respuesta[2] = [" Tambien se crearon Los pagos de autovalúo en estado: pendiente"];

        return $respuesta;
    }
    static public function mdl_CREATE_Recaudo_por_npa($nombre, $precio, $descripcion, $afectos, $fechav, $grupo)
    {
        $slcnpas = RecaudosModelo::mdl_GET_npas_para_create();
        
        $arr_ID_all_asociados = array_column($slcnpas, 'id');
        $arrnpas = array_column($slcnpas, 'npa');

        if ($grupo == "algunos") {
            $arrafectos = RecaudosModelo::expresionNrosToarray($afectos);
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
            $arr_ids_asociados = $arr_ID_all_asociados;
            $cantidadafectos = count($arr_ids_asociados);
            $afectosExpre = "Todos los números de padron activos";
        }

        $fecha = date("Y-m-d H:i:s");
        // conectamos asi para usar lastInsertId()
        $con = Conexion::conectar();
        $stmt = $con->prepare("INSERT INTO recaudos_por_npa(nombre, precio, descripcion, afectos, fechadecreacion, fechadevencimiento,totalafectos)
                                                                         values(:nombre,
                                                                                :precio,
                                                                                :descripcion,
                                                                                :afectos,
                                                                                :fechadecreacion,
                                                                                :fechav,
                                                                                :totalafectos)");
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":precio", $precio, PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(":afectos", $afectosExpre, PDO::PARAM_STR);
        $stmt->bindParam(":fechadecreacion", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":fechav", $fechav, PDO::PARAM_STR);
        $stmt->bindParam(":totalafectos", $cantidadafectos, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $respuesta[0] = "error";
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = 'error en insert recaudo por NPA';
            return $respuesta;
        }
        //para usar  le metodo lastinsert() debe ser pormedio0 de la misma conexion  -->$con
        $id = $con->lastInsertId();

        // creamos los values para la query
        $DataArr = array();

        foreach ($arr_ids_asociados as $id_asociado) {

            $DataArr[] = "('$id_asociado','$id','$precio','0')";
        }
        $values = implode(",", $DataArr);

        $respuesta["arr_id_asciados"]=$arr_ids_asociados;
        $respuesta["slcstands"]=$slcnpas;
        $respuesta["aataArr"]=$DataArr;
        //return $respuesta;

        $stmt = Conexion::conectar()->prepare("INSERT INTO pagos_por_npa(id_asociado,idrecaudo,pago,estado)
                                               VALUES {$values}");

        if (!$stmt->execute()) {
            $respuesta[0] = 'error';
            $respuesta[1] = $stmt->errorInfo();
            $respuesta[2] = 'error en insert pago por NPA';
        }

                
        if ($grupo == "algunos") {
            if (count($rnohabidos) > 0) {
                $rnohabidos = RecaudosModelo::ArrayToexpresionNros($rnohabidos);
                $rnohabidos = explode(",", $rnohabidos);
                $respuesta["no_habidos"] = [$rnohabidos];
            }
        }

        $respuesta[0] = 'ok';
        $respuesta[1] = " El recaudo {$nombre} Registró correctamente <br>
                        Tambien se crearon Los pagos en estado: pendiente  ";
        
       /*  $respuesta["afectos_expre"] = $afectosExpre;
        $respuesta["cant_afectos"] = $cantidadafectos;
        $respuesta["afectos_expre"] = $afectosExpre;
        $respuesta["cant_afectos"] = $cantidadafectos;
        $respuesta["afectos_expre"] = $afectosExpre; */

        return $respuesta;
    }
    static public function mdl_GET_Recaudos()
    {

        $stmt = Conexion::conectar()->prepare('call prc_ListarRecaudos');
        $stmt->execute();
        return $stmt->fetchAll();
        //* cambiar el nombre de la columna numero en tabla stand

    }
    static public function mdl_GET_Recaudos_de_alquiler()
    {

        $stmt = Conexion::conectar()->prepare('SELECT a.id, a.cod_contrato, b.denominacion, precio, concat(ar.nombre,", ",ar.apellido) as nombre, concat(cantidad_periodos,"-",(if (periodo="D","dias","meses"))) as tiempo, fechafin, "" as tool
                                                FROM arriendos a
                                                INNER JOIN bienes_en_arriendo b ON b.id=a.idbien
                                                INNER JOIN arrendatarios  ar ON ar.id=a.idarrendatario
                                                 ');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    static public function mdl_GET_Detalle_Recaudos($strbusqueda)
    {
        // en las tablas el nombre del campo que identifica el recuado es diferente
        if (preg_match('/npa/', $strbusqueda)) {

            $strbusqueda = explode("-", $strbusqueda)[0];

            $stmt = Conexion::conectar()->prepare("SELECT pnpa.id, nrorecibo, a.numerodepadron, concat(a.nombre,', ', a.apellido) as nombre , pago, fechadepago, pnpa.estado 
                                                   FROM pagos_por_npa pnpa
                                                   INNER JOIN asociados a ON a.id=pnpa.id_asociado where idrecaudo=:s1");
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT p.id,nrorecibo,p.id_stand, concat(a.nombre,', ', a.apellido) as nombre, pago, fechadepago, p.estado 
                                                   FROM pagos p
                                                   INNER JOIN stands s ON s.id=p.id_stand
                                                   INNER JOIN asociados a on a.id=s.idasociado
                                                   where idrecaudo=:s1");
        }

        $stmt->bindParam(":s1", $strbusqueda, PDO::PARAM_STR, 12);

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            return $error;
        }

        $todoslospagos = $stmt->fetchAll();

        return $todoslospagos;
    }

    static public function mdl_GET_Recaudos_autocomplete($strbusqueda)
    {

        $stmt = Conexion::conectar()->prepare('SELECT id as id , nombre as nombre 
                                        FROM recaudos WHERE id =:s1 OR nombre LIKE :s2
                                        UNION
                                        SELECT concat("npa-",id) as id , nombre as nombre 
                                        FROM recaudos_por_npa WHERE id =:s1 OR nombre LIKE :s2
                                        
                                        ');
        $strbusqueda = "%" . $strbusqueda . "%";

        $stmt->bindParam(":s1", $strbusqueda, PDO::PARAM_STR, 12);
        $stmt->bindParam(":s2", $strbusqueda, PDO::PARAM_STR, 12);

        $stmt->execute();
        $nombres  = $stmt->fetchAll();
        return $nombres;
    }

    static public function mdl_UPDATE_recaudo($id, $nombre, $precio, $descripcion, $afectos, $fechav)
    {
        if (preg_match('/npa/', $id)) {
            $recaudos = "recaudos_por_npa";
        } else {
            $recaudos = "recaudos";
        }


        $stmt = Conexion::conectar()->prepare('UPDATE ' . $recaudos . ' SET nombre=:nombre,
                                                                    precio=:precio,
                                                                    descripcion=:descripcion,
                                                                    fechadevencimiento=:fechav
                                                         where id=:id');
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR, 50);
        $stmt->bindParam(":precio", $precio, PDO::PARAM_INT, 6);
        $stmt->bindParam(":descripcion", $descripcion, PDO::PARAM_STR, 250);
        //$stmt->bindParam(":afectos", $afectos, PDO::PARAM_STR);
        $stmt->bindParam(":fechav", $fechav, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT, 5);


        if ($stmt->execute()) {
            $respuesta[0] = ['Actulizado'];
            $respuesta[1] = [" Se actualizó correctamente"];
        } else {
            $respuesta = $stmt->errorInfo();
        }
        return $respuesta;
    }

    static public function mdl_DELETE_recaudo($id)
    {




        if (preg_match('/npa/', $id)) {
            $recaudos = "recaudos_por_npa";
            $tblpagos = "pagos_por_npa";
        } else {
            $recaudos = "recaudos";
            $tblpagos = "pagos";
        }

        // comporbamos si existe pagos echos para evitar la eliminacion

        $stmt = Conexion::conectar()->prepare("SELECT * FROM {$tblpagos} WHERE idrecaudo=:id AND estado=1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($pagos) > 0) {
            $respuesta[0] = 'enuso';
            $respuesta[1] = "Ya existen pagos echos de este recaudo.";
            //$respuesta[2] = $pagos;
            return $respuesta;
        }

        $stmt = Conexion::conectar()->prepare("DELETE FROM {$recaudos} where id=:id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $respuesta[0] = ['ok'];
            $respuesta[1] = " se Eliminó correctamente";
            $respuesta[2] = $pagos;
            $respuesta[3] = $recaudos;
        } else {
            $respuesta = $stmt->errorInfo();
        }

        return $respuesta;
    }


    static public function mdl_GET_stand_para_create()
    {
        //cambiamos el estado a los stanes alquilados a  vacio para no generar la deuda
        $stmt = Conexion::conectar()->prepare("UPDATE arriendos a 
                                                INNER JOIN bienes_en_arriendo b ON b.id=a.idbien 
                                                INNER JOIN stands s ON b.codigo=s.nrostand 
                                                SET s.estado =''
                                                where a.fechafin < now() and a.estado='activo'");
        $stmt->execute();

        // seleccionamos los stanes en condicion activo para 
        $stmt = Conexion::conectar()->prepare('SELECT id, cast(nrostand as int) as nrostand, nropisos FROM stands where (estado="activo" or estado="alquilado" or estado="pendiente")  ORDER BY nrostand ASC ');
        $stmt->execute();

        $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);


        return $stmt;
    }
    static public function mdl_GET_npas_para_create()
    {

        // seleccionamos todos los 
        $stmt = Conexion::conectar()->prepare('SELECT id as id, cast(numerodepadron as int) as npa FROM asociados WHERE estado="activo" ORDER BY numerodepadron ASC');
        $stmt->execute();

        $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        return $stmt;
    }
    static public function expresionNrosToarray($expresion)
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

    static public function ArrayToexpresionNros($arraydenumeros)
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
//$ver =new RecaudosModelo();
//$ver->mdl_GET_stand_para_create();