<?php
/**
 * Neo Billing -  Accounting,  Invoicing  and CRM Software
 * Copyright (c) Rajesh Dukiya. All Rights Reserved
 * ***********************************************************************
 *
 *  Email: support@ultimatekode.com
 *  Website: https://www.ultimatekode.com
 *
 *  ************************************************************************
 *  * This software is furnished under a license and may be used and copied
 *  * only  in  accordance  with  the  terms  of such  license and with the
 *  * inclusion of the above copyright notice.
 *  * If you Purchased from Codecanyon, Please read the full License from
 *  * here- http://codecanyon.net/licenses/standard/
 * ***********************************************************************
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class FacturasElectronicas extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        //$this->load->model('customers_model', 'customers');
        $this->load->model("customers_model","customers");
        $this->load->model("facturas_electronicas_model","facturas_electronicas");
        $this->load->library('SiigoAPI');
        $this->load->library("Aauth");
        if (!$this->aauth->is_loggedin()) {
            redirect('/user/', 'refresh');
        }
        if ($this->aauth->get_user()->roleid < 3) {

            exit('<h3>Sorry! You have insufficient permissions to access this section</h3>');

        }
    }
    public function index(){
        $head['title'] = "Administrar Facturas Electronicas Customer";
        $head['usernm'] = $this->aauth->get_user()->username;
$this->load->model("customers_model","customers");
        $data['servicios'] = $this->customers->servicios_detail($_GET['id']);
        $data['due'] = $this->customers->due_details($_GET['id']);
        $this->load->view('fixed/header', $head);
        $this->load->view('customers/facturas_electronicas',$data);
        $this->load->view('fixed/footer');
    }

    public function ajax_list()
    {
        $this->load->model('Facturas_electronicas_model', 'facturas');

        $list = $this->facturas->get_datatables();
        $data = array();

        $no = $this->input->post('start');

        foreach ($list as $facturas) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $facturas->id;
            $row[] = dateformat($facturas->fecha);
            $row[] = $facturas->customer_id;
            if($facturas->invoice_id=="" || $facturas->invoice_id==null || $facturas->invoice_id=="")
            $row[] = $facturas->invoice_id;
            $row[] = $facturas->servicios_facturados;
            $row[] = amountFormat($facturas->s1TotalValue);
           // $row[] = '<span class="st-' . $invoices->status . '">' . $invoices->status . '</span>';           
           // $row[] = '<a href="' . base_url("purchase/view?id=$invoices->tid") . '" class="btn btn-success btn-xs"><i class="icon-file-text"></i> ' . $this->lang->line('View') . '</a> &nbsp; <a href="' . base_url("purchase/printinvoice?id=$invoices->tid") . '&d=1" class="btn btn-info btn-xs"  title="Download"><span class="icon-download"></span></a>&nbsp';
            

            $data[] = $row;
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->facturas->count_all(),
            "recordsFiltered" => $this->facturas->count_filtered(),
            "data" => $data,
        );
        //output to json format
        echo json_encode($output);

    }
    public function guardar(){
        $this->load->library('SiigoAPI');
        $this->facturas_electronicas->cargar_configuraciones_para_facturar();
        $api = new SiigoAPI();
        $v1=$api->getAuth(1);
        $this->db->update("config_facturacion_electronica",array("tocken"=>$v1['access_token']),array("id"=>1));
        $v1=$api->getAuth2(2);
        $this->db->update("config_facturacion_electronica",array("tocken"=>$v1['access_token']),array("id"=>2));
        $this->load->model("customers_model","customers");
         $dataApiTV=null;
        $dataApiNET=null;
        if($_POST['servicios']=="Combo"){
            $dataApiNET=$this->customers->getFacturaElectronica(null);
            //var_dump($dataApiNET);
            if(isset($_POST['puntos']) && $_POST['puntos']!="no"){
                $dataApiTV= $this->customers->getFacturaElectronica(2);//verificar este caso
            }else{
                $dataApiTV= $this->customers->getFacturaElectronica(null);    
            }
        }else if($_POST['servicios']=="Internet"){
            $dataApiNET= $this->customers->getFacturaElectronica(null);
        }else if($_POST['servicios']=="Television"){
            if(isset($_POST['puntos']) && $_POST['puntos']!="no"){
                $dataApiTV= $this->customers->getFacturaElectronica(2);    //y este caso
            }else{
                $dataApiTV= $this->customers->getFacturaElectronica(null);    
            }            
        }
        
         $centro_de_costo_code="1074";
        $centro_de_costo_codeNET="69";
       


        //$consecutivo_siigo=$this->db->select("max(consecutivo_siigo)+1 as consecutivo_siigo")->from("facturacion_electronica_siigo")->get()->result();
        /*$dataApiTV->Header->Number=$consecutivo_siigo[0]->consecutivo_siigo;

        if($dataApiTV->Header->Number=="1" || $dataApiTV->Header->Number==NULL || $dataApiTV->Header->Number=="0"){
            $dataApiTV->Header->Number=500;
        }*/
        //customer data and facturacion_electronica_siigo table insert
        $customer = $this->db->get_where("customers",array('id' =>$_POST['id']))->row();
        //cuadrando customer para crear o actualizar en siigo
        
        $json_customer=json_decode($this->customers->getCustomerJson());
//var_dump($json_customer->address->address);
//echo "<br>";
        $json_customer->identification=$customer->documento;
        
       
        $firs_name=strtoupper(str_replace("?", "Ñ",$customer->name));
        $second_name=strtoupper(str_replace("?", "Ñ",$customer->dosnombre));
        $first_last_name=strtoupper(str_replace("?", "Ñ",$customer->unoapellido));
        $second_last_name=strtoupper(str_replace("?", "Ñ",$customer->dosapellido));
        $json_customer->name[0]=$firs_name." ".$second_name;
        $json_customer->name[1]=$first_last_name." ".$second_last_name;
        /*validaciones por tipo documento*/
            if($customer->tipo_documento=="NIT"){
                $json_customer->id_type="31";
                $json_customer->person_type="Company";
                $json_customer->name[0].=" ".$first_last_name." ".$second_last_name;
                unset($json_customer->name[1]);
            }
             /*end validaciones por tipo documento*/
        $json_customer->address->address=$customer->nomenclatura . ' ' . $customer->numero1 . $customer->adicionauno.' Nº '.$customer->numero2.$customer->adicional2.' - '.$customer->numero3;
        //$tv_product= $this->db->get_where("products", array('pid' => "27"))->row();
           /* if($customer->gid==4 ){//monterrey
                        $json_customer->address->city->city_code="85162";
                        $centro_de_costo_code="1070";
                        $centro_de_costo_codeNET="165";                                   
            }else if($customer->gid==3 ){//villanueva
                $json_customer->address->city->city_code="85440";                                   
                $centro_de_costo_code="1072";
                $centro_de_costo_codeNET="167";
            }else if($customer->gid==5 ){//mocoa
                $json_customer->address->city->state_code="86";                                   
                $json_customer->address->city->city_code="86001";   
                //$tv_product= $this->db->get_where("products", array('pid' => "159"))->row();                                
            }*/
            if($customer->gid==6 ){//monterrey
                        $json_customer->address->city->city_code="13006";
                        //$centro_de_costo_code="1070";
                        //$centro_de_costo_codeNET="165";    
            }

            //var_dump(preg_match_all("/[^0-9]/",$customer->celular));
            
            if(strlen($customer->celular)>10 || preg_match_all("/[^0-9]/",$customer->celular)!=0){
                $customer->celular="0";
            }
            if(strlen($customer->celular2)>10 || preg_match_all("/[^0-9]/",$customer->celular2)!=0){
                $customer->celular2="0";
            }
            //falta validar que si no tiene celular al crear elimine el array de contactos para que no quede en cero y luego al actualizar cliente cree un contacto aparte y quede ese en 0
                   
            $json_customer->phones[0]->number=$customer->celular;
            $json_customer->contacts[0]->first_name=$firs_name." ".$second_name;
            $json_customer->contacts[0]->last_name=$first_last_name." ".$second_last_name;
            $json_customer->contacts[0]->email="facturacionachi2022@gmail.com";

            $json_customer->contacts[0]->phone->number=$customer->celular;
            $json_customer->comments="Estrato : ".$customer->estrato;
    //llenando los datos para crear o actualizar segun sea el caso
           // var_dump($json_customer);
            
            $json_customer=json_encode($json_customer);


           
            
        
        // end cuadrando customer para crear o actualizar en siigo
        //data siigo api
        $dateTime=new DateTime($_POST['sdate']);
            
        //cambio de fecha de vencimiento sumandole 20 dias a la fecha seleccionada
            $fecha_actual = date($dateTime->format("Y-m-d"));
            $date=date("d-m-Y",strtotime($fecha_actual."+ 20 days")); 
            $dateTimeVencimiento=new DateTime($date);
        //end fecha vencimiento
        
        if($dataApiTV!=null){
            $dataApiTV=json_decode($dataApiTV);
            $dataApiTV->document->id="27147";
            $dataApiTV->customer->identification=$customer->documento;
            //$dataApiTV->cost_center=$centro_de_costo_code;
            $dataApiTV->seller="441";
            $dataApiTV->date=$dateTime->format("Y-m-d");
            $dataApiTV->payments[0]->due_date=$dateTimeVencimiento->format("Y-m-d");
            $dataApiTV->observations="Estrato : ".$customer->estrato;
            $dataApiTV->payments[0]->id="3943";

            $ob1=$this->db->get_where("config_facturacion_electronica",array("id"=>1))->row();
            $consulta_siigo1=$api->getCustomer1($customer->documento,$ob1->tocken);
           
            
            if(isset($consulta_siigo1) && isset($consulta_siigo1['pagination']) && isset($consulta_siigo1['pagination']['total_results']) && $consulta_siigo1['pagination']['total_results']==0){
                    $api->saveCustomer1($json_customer,$ob1->tocken);//para crear cliente en siigo si no existe
            }else{
                    //$api->updateCustomer($json_customer,$consulta_siigo1['results'][0]['id'],1);//para acturalizar cliente en siigo 
            }
        }
        if($dataApiNET!=null){
            $dataApiNET=json_decode($dataApiNET);
            $dataApiNET->document->id="26604";
            $dataApiNET->customer->identification=$customer->documento;
            //$dataApiNET->cost_center=$centro_de_costo_codeNET;
            $dataApiNET->seller="55";
            $dataApiNET->date=$dateTime->format("Y-m-d");
            $dataApiNET->payments[0]->due_date=$dateTimeVencimiento->format("Y-m-d");
            $dataApiNET->observations="Estrato : ".$customer->estrato;
            $dataApiNET->payments[0]->id="478";

             $ob1=$this->db->get_where("config_facturacion_electronica",array("id"=>2))->row();
            $consulta_siigo1=$api->getCustomer1($customer->documento,$ob1->tocken);
          
            
            if(isset($consulta_siigo1) && isset($consulta_siigo1['pagination']) && isset($consulta_siigo1['pagination']['total_results']) && $consulta_siigo1['pagination']['total_results']==0){
                    $json_customer=json_decode($json_customer);
                    $json_customer->related_users->seller_id=55;
                    $json_customer->related_users->collector_id=55;
                    $json_customer->contacts[0]->email="facturacionachi2022@gmail.com";
                    $json_customer=json_encode($json_customer);
                    //$json_customer=str_replace("321", "282", subject)
                    $api->saveCustomer1($json_customer,$ob1->tocken);//para crear cliente en siigo si no existe
            }else{
                    $json_customer=json_decode($json_customer);
                    $json_customer->related_users->seller_id=55;
                    $json_customer->related_users->collector_id=55;
                    $json_customer->contacts[0]->email="facturacionachi2022@gmail.com";
                    $json_customer=json_encode($json_customer);
                    $api->updateCustomer($json_customer,$consulta_siigo1['results'][0]['id'],2);//para acturalizar cliente en siigo 
            }
        }
        
        
        //var_dump($dataApiNET);
        //falta el manejo de los saldos saldos
        
        //falta el manejo de los saldos saldos
        if($dataApiTV!=null){
            $dataApiTV->items[0]->description="Servicio de Televisión por Cable";
            /*if($tv_product->taxrate!=0){
                $precios=$this->customers->calculoParaFacturaElectronica($tv_product->product_price);
                $dataApiTV->items[0]->price=$precios['valor_sin_iva'];
                $dataApiTV->items[0]->taxes->value=$precios['valor_iva'];
                $dataApiTV->payments[0]->value=$precios['valortotal'];
            }else{

            }*/
            unset($dataApiTV->items[0]->taxes);
            if(isset($_POST['puntos']) && $_POST['puntos']!="no"){
                    $dataApiTV->items[1]->description="Puntos de tv adicionales ".$_POST['puntos'];
                    $lista_de_productos=$this->db->from("products")->where("pid","158")->get()->result();
                    $prod=$lista_de_productos[0];

                    $prod->product_price=$prod->product_price*intval($_POST['puntos']);

                    $dataApiTV->items[1]->code="001";

                            $dataApiTV->items[1]->price=$prod->product_price;
                            $dataApiTV->payments[0]->value=$dataApiTV->payments[0]->value+$prod->product_price;                            

            }

            //falta verificar el caso de la tv de mocoa que cambian los valores
        }
         //var_dump($dataApiNET);
        $producto_existe=false;
         if($dataApiNET!=null){
            $array_servicios=$this->customers->servicios_detail($customer->id);
            if($array_servicios['combo']!="no"){
                $dataApiNET->items[0]->description="Servicio de Internet ".$array_servicios['combo'];
                $lista_de_productos=$this->db->from("products")->like("product_name","mega","both")->get()->result();
                $array_servicios['combo']=strtolower(str_replace(" ", "",$array_servicios['combo'] ));
                foreach ($lista_de_productos as $key => $prod) {
                    $prod->product_name=strtolower(str_replace(" ", "",$prod->product_name ));
                    if($prod->product_name==$array_servicios['combo']){
                        $producto_existe=true;
                        //var_dump($prod->product_name);
                        $dataApiNET->items[0]->code="I01";

                        if($prod->taxrate!=0){

                            //$precios=$this->customers->calculoParaFacturaElectronica($prod->product_price);
                            $v1=($prod->product_price*19)/100;
                            $v2=$v1+$prod->product_price;
                            $dataApiNET->items[0]->taxes[0]->id=13534;
                            $dataApiNET->items[0]->price=$prod->product_price;
                            $dataApiNET->items[0]->taxes[0]->value=$v1;
                            $dataApiNET->payments[0]->value=$v2;

                        }else{
                            unset($dataApiNET->items[0]->taxes);
                            $dataApiNET->items[0]->price=$prod->product_price;                            
                            $dataApiNET->payments[0]->value=$prod->product_price;

                        }
                        
                        
                        break;
                    }
                }
            }
            //falta esta parte identificar el paquete de internet del usuario y agregar sus valores
        }

        /*var_dump($dateTime->format("Ymd"));
        var_dump($dataApi->Payments[0]->DueDate);
        var_dump($dataApi->Header->Number);
        var_dump($dataApi->Header->Account->Phone->Number);*/
        //facturacion_electronica_siigo table insert        
        $dataInsert=array();
        $dataInsert['consecutivo_siigo']=0;
        $dataInsert['fecha']=$dateTime->format("Y-m-d");
        $dataInsert['customer_id']=$_POST['id'];
        $dataInsert['servicios_facturados']=$_POST['servicios'];
        // end customer data facturacion_electronica_siigo table insert
        
        $dataApiTV=json_encode($dataApiTV);
         //var_dump($dataApiTV);
        $dataApiNET=json_encode($dataApiNET); 
        //var_dump($dataApiNET);
        //var_dump($dataApiTV);
        $retorno=array("mensaje"=>"No");
        if($dataApiTV!=null && $dataApiTV!="null"){
            $ob1=$this->db->get_where("config_facturacion_electronica",array("id"=>1))->row();
            $retorno = $api->accionar2($api,$dataApiTV,$ob1->tocken); 
            
            if($dataApiNET!=null && $dataApiNET!="null"){
                $ob1=$this->db->get_where("config_facturacion_electronica",array("id"=>2))->row();
                $retorno = $api->accionar2($api,$dataApiNET,$ob1->tocken);  
            }
        }else if($dataApiNET!=null && $dataApiNET!="null"){
            $ob1=$this->db->get_where("config_facturacion_electronica",array("id"=>2))->row();
            $retorno = $api->accionar2($api,$dataApiNET,$ob1->tocken);         
        }

        if($retorno['mensaje']=="Factura Guardada"){
            $this->db->insert("facturacion_electronica_siigo",$dataInsert);
            redirect("facturasElectronicas?id=".$customer->id);
        }else{
            /*$error_era_consecutivo=false;
            for ($i=1; $i < 10 ; $i++) { 
                $dataApi=json_decode($dataApi);
                $dataApi->Header->Number= intval($dataApi->Header->Number)+$i;
                $dataInsert['consecutivo_siigo']=$dataApi->Header->Number;
                $dataApi=json_encode($dataApi);
                $retorno2 = $api->accionar($api,$dataApi);              
                if($retorno2['mensaje']=="Factura Guardada"){
                    $this->db->insert("facturacion_electronica_siigo",$dataInsert);
                    $error_era_consecutivo=true;
                    $i=11;
                    redirect("facturasElectronicas?id=".$customer->id);
                    break;
                }
            }*/
            //if($error_era_consecutivo==false){
                var_dump($retorno['respuesta']);
            //}
        }
    }
    public function activar_desactivar_usuario(){
        $id=$this->input->post("id");
        $selected=$this->input->post("selected");
        if($selected=="true"){
            $data['facturar_electronicamente']=1;
        }else{
            $data['facturar_electronicamente']=0;
        }
        $this->db->update("customers",$data,array("id"=>$id));
        echo json_encode(array("status"=>"guardao"));
        
    }
    public function get_datos_customer(){
        $this->load->model('customers_model', 'customers');
        $id=$this->input->post("id");
        $servicios=$this->customers->servicios_detail($id);
        $puntos = $this->customers->due_details($id);
        $customer=$this->db->get_where("customers",array("id"=>$id))->row();

        echo json_encode(array("status"=>"success","f_elec_tv"=>$customer->f_elec_tv,"f_elec_internet"=>$customer->f_elec_internet,"f_elec_puntos"=>$customer->f_elec_puntos,"servicios"=>$servicios,"puntos"=>$puntos['puntos']));
    }
    public function guardar_seleccion_usuario(){
        $id=$this->input->post("id");
        $servicio=$this->input->post("servicio");
        $puntos=$this->input->post("puntos");
        $data['f_elec_tv']=null;
        $data['f_elec_internet']=null;
        $data['f_elec_puntos']=null;
        if($servicio=="Internet"){
            $data['f_elec_internet']=1;
            $data['f_elec_tv']=0;
        }else if($servicio=="Television"){
            $data['f_elec_tv']=1;
            $data['f_elec_internet']=0;
        }else{
            $data['f_elec_internet']=1;
            $data['f_elec_tv']=1;
        }
        if($servicio=="Television" || $servicio=="Combo"){
            if($puntos=="si"){
                $data['f_elec_puntos']=1;
            }else if($puntos=="no"){
                $data['f_elec_puntos']=0;
            }
        }
        $this->db->update("customers",$data,array("id"=>$id));
        echo json_encode(array("status"=>"success"));
        
    }
    public function generar_facturas_electronicas_multiples(){
        $this->load->model('customers_model', 'customers');
        $this->load->model('transactions_model');
        $head['title'] = "Generar Facturas Electronicas";
        $head['usernm'] = $this->aauth->get_user()->username;
        $data['accounts'] = $this->transactions_model->acc_list();
        $this->facturas_electronicas->cargar_configuraciones_para_facturar();
        $this->load->view('fixed/header', $head);
        $this->load->view('facturas_electronicas/configuraciones',$data);
        $this->load->view('fixed/footer');       
    }
    public function visualizar_resumen_ejecucion(){
        $head['title'] = "Facturas electronicas generadas ";        
        
        $data['fecha'] = $_GET['fecha'];
        $data['pay_acc'] = $_GET['sede'];
        $head['usernm'] = $this->aauth->get_user()->username;
        $this->load->view('fixed/header', $head);
        $this->load->view('facturas_electronicas/facturas_creadas', $data);
        $this->load->view('fixed/footer');
    }
    public function obtener_lista_usuarios_a_facturar(){
        $numero_total;
        $this->facturas_electronicas->cargar_configuraciones_para_facturar();
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        $dateTime=new DateTime($_POST['sdate']);
        
        
        $api = new SiigoAPI();
        $v1=$api->getAuth(1);
        $this->db->update("config_facturacion_electronica",array("tocken"=>$v1['access_token']),array("id"=>1));
        $v1=$api->getAuth2(2);
        $this->db->update("config_facturacion_electronica",array("tocken"=>$v1['access_token']),array("id"=>2));
        //$_SESSION['api_siigo']=$api;
        $_SESSION['errores']=array();

        $customers_t = $this->db->query("select id from customers where (usu_estado='Activo' or usu_estado='Compromiso') and (gid ='".$caja1->sede."' and facturar_electronicamente='1')")->result_array();//and id=8241
        $numero_total=count($customers_t);
        $usuarios_restantes_lista = $this->db->query("select customers.id from customers LEFT join facturacion_electronica_siigo on customers.id=facturacion_electronica_siigo.customer_id and fecha='".$dateTime->format("Y-m-d")."' where (customers.usu_estado='Activo' or customers.usu_estado='Compromiso') and (customers.gid ='".$caja1->sede."' and customers.facturar_electronicamente='1') and facturacion_electronica_siigo.id is null")->result_array();//and id=8241
        $array_return =array("total_usuarios"=>$numero_total,"lista_usuarios_a_facturar"=>$usuarios_restantes_lista);
        echo json_encode($array_return);
    }
    public function procesar_usuarios_a_facturar(){
        $dateTime=new DateTime($_POST['sdate']);
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        $se_facturo = $this->db->query("SELECT * FROM facturacion_electronica_siigo WHERE fecha ='".$dateTime->format("Y-m-d")."' and customer_id=".$_POST['id_customer'])->result_array();//and id=8241
        $retorno=array();
        if(count($se_facturo)!=0){
            $retorno["estado"]="procesado 2";
            echo json_encode($retorno);
        }else{
            $ret=$this->facturar_customer($_POST['id_customer'],$_POST['sdate']);
            $retorno["estado"]="procesado";
            echo json_encode($retorno);
        }
        
        

    }
    public function fe(){
        $query=$this->db->query("select * from customers where (usu_estado='Activo' or usu_estado='Compromiso') and (gid ='3' and facturar_electronicamente='1' and (f_elec_tv=1 or  f_elec_tv is null))")->result_array();
        $num=0;
        foreach ($query as $key => $value) {
            $servicios=$this->customers->servicios_detail($value['id']);
            if($servicios['television']!="no" && $servicios['television']!="-" &&$servicios['television']!="" &&$servicios['television']!="null" && $servicios['television']!=null){
                $num++;
            }else{
                echo $value['id']."<br>";
            }
        }
        echo "TOTAL : ".$num;
    }
    public function facturar_customer($id_customer,$sdate){
set_time_limit(150);
        ini_set ( 'max_execution_time', 150);
        ini_set ( 'max_execution_time', 150);
            $servicios=$this->customers->servicios_detail($id_customer);
                $puntos = $this->customers->due_details($id_customer);
                $api = new SiigoAPI();
                //guardare en un array la variable servicios = combo o tv o internet y la variable puntos con no o el numero de puntos
                // el orden es prima los servicios que tiene actualmente como hay seleccion por el admin si el servicio existe se toma la seleccion si no se omite,
                //°° IMPORTANTE °°  por otro lado si agrega servicios y estan seteadas las opciones con un solo servicio ejemplo, el admin debe de setear las opciones de facturacion electronica porque generara segun este seteado
                $customer_data=$this->db->get_where("customers",array("id"=>$id_customer))->row();

                $datos=array();
                if($puntos['puntos']=="0"){
                    $datos['puntos']="no";
                }else{
                    $datos['puntos']=$puntos['puntos'];
                }
                if($customer_data->f_elec_puntos=="0"){
                    $datos['puntos']="no";
                }
                $datos['servicios']=null;
                if($servicios['television']!="no" && $servicios['television']!="-" &&$servicios['television']!="" &&$servicios['television']!="null" && $servicios['television']!=null){
                    
                    if($servicios['combo']!="no" && $servicios['combo']!="-" && $servicios['combo']!="" && $servicios['combo']!="null" && $servicios['combo']!=null){
                            $datos['servicios']="Combo";
                            if($customer_data->f_elec_internet=="0"){
                                $datos['servicios']="Television";
                            }else if($customer_data->f_elec_tv=="0"){
                                $datos['servicios']="Internet";
                            }
                    }else{
                        $datos['servicios']="Television";    
                    }                    
                }else if($servicios['combo']!="no" && $servicios['combo']!="-" && $servicios['combo']!="" && $servicios['combo']!="null" && $servicios['combo']!=null){
                      $datos['servicios']="Internet";                    
                }
                $datos['sdate']=$sdate;
                $datos['id']=$id_customer;
                if($datos['servicios']!=null){
                    
                   
            
                        $creo=$this->facturas_electronicas->generar_factura_customer_para_multiple($datos,$api);
                        //$creo=array("status"=>true);
                        //sleep(7);
                        if($creo['status']==true){
                                return  true;                        
                        }else{
                            $_SESSION['errores'][]=array("id"=>$value['id'],"error"=>$creo['respuesta']);                            
                            return false;
                        }
                   
                    //--CostCenterCode para agregar la sede 
                    //--falta agregar el centro de costo 
                    //se agrego centro de costo falta validar las demas sedes
                    // y validar que si ya se creo la factura en esta fecha no volverla a crear

                }
    }
    public function generar_facturas_ajax(){
        //la idea es desde el cliente dar la orden de consultar el siguiente usuario que falte e ir generando y retornando el resultado;
        //uno a uno
        $this->load->model("customers_model","customers");
        $this->load->model("facturas_electronicas_model","facturas_electronicas");
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        $customers = $this->db->query("select * from customers where (usu_estado='Activo' or usu_estado='Compromiso') and (gid ='".$caja1->sede."' and facturar_electronicamente='1')")->result_array();//and id=8241
        $datos_del_proceso=array("facturas_creadas"=>array(),"facturas_con_errores"=>array(),"facturas_anteriormente_creadas"=>array());
        $dateTime=new DateTime($_POST['sdate']);
        $x=0;
        $cuenta=0;
        $datos_file=array();        
        $total_customer=count($customers);
        $total_f_creadas=$this->db->query("SELECT COUNT(*) as cuenta_f FROM `facturacion_electronica_siigo` inner join customers on customers.id=facturacion_electronica_siigo.customer_id where customers.gid='".$caja1->sede."' and facturacion_electronica_siigo.fecha = '".$dateTime->format("Y-m-d")."'")->result_array();
        $total_f_creadas=intval($total_f_creadas[0]['cuenta_f']);
        
                $file = fopen("assets/facturas_electronicas_seguimiento_".$_POST['pay_acc'].".txt", "w");            
                fwrite($file, $cuenta.",".$total_customer.",".$total_f_creadas);
                fclose($file);
                $file = fopen("assets/facturas_electronicas_seguimiento.txt", "w");            
                fwrite($file, "inicio");
                fclose($file);

    }
    public function generar_facturas_action(){
        set_time_limit(10000);
        ini_set ( 'max_execution_time', 10000);
        ini_set ( 'max_execution_time', 10000);
       
        $this->load->model("customers_model","customers");
        $this->load->model("facturas_electronicas_model","facturas_electronicas");
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        
        $customers = $this->db->query("select * from customers where (usu_estado='Activo' or usu_estado='Compromiso') and (gid ='".$caja1->sede."' and facturar_electronicamente='1')")->result_array();//and id=8241
        $datos_del_proceso=array("facturas_creadas"=>array(),"facturas_con_errores"=>array(),"facturas_anteriormente_creadas"=>array());
        $dateTime=new DateTime($_POST['sdate']);
        $x=0;
         $this->load->library('SiigoAPI');
        $api = new SiigoAPI();
        $api->getAuth(1);
        $api->getAuth2(2);
        $cuenta=0;
        $datos_file=array();        
        $total_customer=count($customers);
        $total_f_creadas=$this->db->query("SELECT COUNT(*) as cuenta_f FROM `facturacion_electronica_siigo` inner join customers on customers.id=facturacion_electronica_siigo.customer_id where customers.gid='".$caja1->sede."' and facturacion_electronica_siigo.fecha = '".$dateTime->format("Y-m-d")."'")->result_array();
        $total_f_creadas=intval($total_f_creadas[0]['cuenta_f']);
        
                $file = fopen("assets/facturas_electronicas_seguimiento_".$_POST['pay_acc'].".txt", "w");            
                fwrite($file, $cuenta.",".$total_customer.",".$total_f_creadas);
                fclose($file);
                $file = fopen("assets/facturas_electronicas_seguimiento.txt", "w");            
                fwrite($file, "inicio");
                fclose($file);
        
        foreach ($customers as $key => $value) {
            $x1=file_get_contents($_SERVER['DOCUMENT_ROOT'].'/CRMvestel/assets/facturas_electronicas_seguimiento.txt', FILE_USE_INCLUDE_PATH);
            if($x1=="detener"){
                break;
                exit;
            }
            $cuenta++;
            
                $servicios=$this->customers->servicios_detail($value['id']);
                $puntos = $this->customers->due_details($value['id']);
                //guardare en un array la variable servicios = combo o tv o internet y la variable puntos con no o el numero de puntos
                // el orden es prima los servicios que tiene actualmente como hay seleccion por el admin si el servicio existe se toma la seleccion si no se omite,
                //°° IMPORTANTE °°  por otro lado si agrega servicios y estan seteadas las opciones con un solo servicio ejemplo, el admin debe de setear las opciones de facturacion electronica porque generara segun este seteado
                

                $datos=array();
                if($puntos['puntos']=="0"){
                    $datos['puntos']="no";
                }else{
                    $datos['puntos']=$puntos['puntos'];
                }
                if($value['f_elec_puntos']=="0"){
                    $datos['puntos']="no";
                }
                $datos['servicios']=null;
                if($servicios['television']!="no" && $servicios['television']!="-" &&$servicios['television']!="" &&$servicios['television']!="null" && $servicios['television']!=null){
                    
                    if($servicios['combo']!="no" && $servicios['combo']!="-" && $servicios['combo']!="" && $servicios['combo']!="null" && $servicios['combo']!=null){
                            $datos['servicios']="Combo";
                            if($value['f_elec_internet']=="0"){
                                $datos['servicios']="Television";
                            }else if($value['f_elec_tv']=="0"){
                                $datos['servicios']="Internet";
                            }
                    }else{
                        $datos['servicios']="Television";    
                    }                    
                }else if($servicios['combo']!="no" && $servicios['combo']!="-" && $servicios['combo']!="" && $servicios['combo']!="null" && $servicios['combo']!=null){
                      $datos['servicios']="Internet";                    
                }
                $datos['sdate']=$_POST['sdate'];
                $datos['id']=$value['id'];
                if($datos['servicios']!=null){
                    
                    $fecha_1=$dateTime->format("Y-m-d");
                    $factura_tabla=$this->db->get_where("facturacion_electronica_siigo",array("fecha"=>$fecha_1,'customer_id'=>$value['id']))->row();
                    if(empty($factura_tabla)){

$x++;
            //var_dump($x);
            //echo date('h:i:s') . "\n";
                        $creo=$this->facturas_electronicas->generar_factura_customer_para_multiple($datos,$api);
                        $creo=array("status"=>true);
                        //sleep(7);
                        if($creo['status']==true){
                            $datos_del_proceso['facturas_creadas'][]=$value['id'];
                            $total_f_creadas++;
                        }else{
                            $datos_del_proceso['facturas_con_errores'][]=array("id"=>$value['id'],"error"=>$creo['respuesta']);
                        }
                    }else{
                            $datos_del_proceso['facturas_anteriormente_creadas'][]=$value['id'];
                    }
                    //--CostCenterCode para agregar la sede 
                    //--falta agregar el centro de costo 
                    //se agrego centro de costo falta validar las demas sedes
                    // y validar que si ya se creo la factura en esta fecha no volverla a crear

                }

            if($cuenta%2==0 || $cuenta>=($total_customer-2)){

                    $file = fopen("assets/facturas_electronicas_seguimiento_".$_POST['pay_acc'].".txt", "w");            
                    fwrite($file, $cuenta.",".$total_customer.",".$total_f_creadas);
                    fclose($file);
            }

        }
        $_SESSION['errores']=$datos_del_proceso['facturas_con_errores'];
     //   var_dump($datos_del_proceso);
        //$head['title'] = "Facturas electronicas generadas ";        
        $data=array();
        //$data['datos_del_proceso'] = $datos_del_proceso;
        $data['fecha'] = $dateTime->format("Y-m-d");
        $data['sede'] = $caja1->sede;
         $data_h=array();
            $data_h['modulo']="Ventas";
            $data_h['accion']="Facturacion electronica multiple {insert}";
            $data_h['id_usuario']=$this->aauth->get_user()->id;
            $data_h['fecha']=date("Y-m-d H:i:s");
            $data_h['descripcion']="Se genera Facturacion electronica para la sede ".$caja1->holder." en la fecha ".$dateTime->format("Y-m-d");
            $data_h['id_fila']=0;
            $data_h['tabla']="facturacion_electronica_siigo";
            $data_h['nombre_columna']="";
            $this->db->insert("historial_crm",$data_h);
        //$head['usernm'] = $this->aauth->get_user()->username;
        /*$this->load->view('fixed/header', $head);
        $this->load->view('facturas_electronicas/facturas_creadas', $data);
        $this->load->view('fixed/footer');*/
        echo json_encode($data);
        
    }
    public function obtener_token(){
        $this->load->library('SiigoAPI');
        $api = new SiigoAPI();
        $x=$api->getAuth();
        //var_dump($x);

       /* $ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, "https://api.siigo.com/auth");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
          \"username\": \"contabilidad@vestel.com.co\",
          \"access_key\": \"MDc2YzZlMzAtZGI2Yy00OGFkLWFjZjktZTNlNGUxNDZkODk5Ok9SUDkhOXowQ0E=\"
        }");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);*/
$x=json_decode($x);
//var_dump($x->access_token);
        $ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_URL, "https://api.siigo.com/v1/customers?identification=123456788");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Content-Type: application/json",
  "Authorization: Bearer ".$x->access_token
));

$response = curl_exec($ch);
curl_close($ch);
var_dump($response);

    }
    public function lista_facturas_generadas(){
        $dt= new DateTime($_GET['fecha']);
        $caja1=$this->db->get_where('accounts',array('id' =>$_GET['pay_acc']))->row();
        $lista_invoices=$this->db->query("SELECT *,facturacion_electronica_siigo.id as id_fac_elec FROM facturacion_electronica_siigo inner join customers on customers.id=facturacion_electronica_siigo.customer_id where fecha='".$dt->format("Y-m-d")."' and gid='".$caja1->sede."'")->result_array();
        $no = $this->input->post('start');
        $data=array();
        $x=0;
        $minimo=$this->input->post('start');
        $maximo=$minimo+10;
        foreach ($lista_invoices as $key => $value) {
            
            if($x>=$minimo && $x<$maximo){
                $no++;
                $customers = $this->db->get_where("customers", array('id' => $value['customer_id']))->row();
                $row = array();
                $row[] = $no;
                //$row[] = $customers->abonado;
                $row[] = '<a href="'.base_url().'customers/view?id=' . $customers->id . '">' . $customers->name ." ". $customers->unoapellido. '</a>';
                $row[] = $customers->celular;
                $row[] = $customers->documento;
                //$row[] = $customers->nomenclatura . ' ' . $customers->numero1 . $customers->adicionauno.' Nº '.$customers->numero2.$customers->adicional2.' - '.$customers->numero3;
                //$row[] = $customers->usu_estado;
                
                $row[] = '<a href="#" id="id_'.$value["id_fac_elec"].'" data-nombre="'.$customers->name .' '. $customers->unoapellido.'" onclick="eliminar_factura_electronica('.$value['id_fac_elec'].');" class="btn btn-info btn-sm"><span class="icon-trash"></span>  Elminar</a>';//'<a href="'.base_url().'customers/invoices?id='.$value['csd'].'" class="btn btn-info btn-sm"><span class="icon-eye"></span>  Facturas</a> <a href="'.base_url().'invoices/view?id='.$value['tid'].'" class="btn btn-info btn-sm"><span class="icon-eye"></span>  Factura Creada</a>';
                $data[] = $row;

            }
            $x++;
             
             
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => count($lista_invoices),
            "recordsFiltered" => count($lista_invoices),
            "data" => $data,
        );
        //output to json format
        echo json_encode($output);
    }
    public function delete_factura_electronica_local(){
        $this->db->delete("facturacion_electronica_siigo",array("id"=>$_POST['id']));
        echo "eliminado";
    }
     public function lista_facturas_no_generadas(){
        

        $lista_invoices=$_SESSION['errores'];
        $no = $this->input->post('start');
        $data=array();
        $x=0;
        $minimo=$this->input->post('start');
        $maximo=$minimo+10;
        foreach ($lista_invoices as $key => $value) {
            
            if($x>=$minimo && $x<$maximo){
                $no++;
                $customers = $this->db->get_where("customers", array('id' => $value['id']))->row();
                $row = array();
                $row[] = $no;
                //$row[] = $customers->abonado;
                $row[] = '<a href="customers/view?id=' . $customers->id . '">' . $customers->name ." ". $customers->unoapellido. '</a>';
                $row[] = $customers->celular;
                $row[] = $customers->documento;
                //$row[] = $customers->nomenclatura . ' ' . $customers->numero1 . $customers->adicionauno.' Nº '.$customers->numero2.$customers->adicional2.' - '.$customers->numero3;
                //$row[] = $customers->usu_estado;
                $row[] = $value['error'];
                $data[] = $row;

            }
            $x++;
             
             
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => count($lista_invoices),
            "recordsFiltered" => count($lista_invoices),
            "data" => $data,
        );
        //output to json format
        echo json_encode($output);
    }
     public function recorrer_facturas(){
            ob_end_clean();
        $fecha="2022-08-17";
        $fecha_filtro="2022-08-17";
        $api = new SiigoAPI();
        $api->getAuth(1);
        $api->getAuth2(2);
        $page_total=25;
        
        /* //descomentar esto para eliminacion
        for ($i=1; $i <=$page_total ; $i++) { 
            $lista_x=json_decode($api->getInvoices($i,$fecha));
            //var_dump($lista_x);
            foreach ($lista_x->results as $key => $value) {
                //var_dump($value->id);
                $data['id_invoice']=$value->id;
                $data['nombre']=$value->name;
                $data['fecha']=$value->date;
                $this->db->insert("filas_a_borrar",$data);
            }    
        }
        */
                    


    }
    public function borrar_facturas_v(){
        $this->load->model('customers_model', 'customers');
        $this->load->model('transactions_model');
        $head['title'] = "Generar Facturas Electronicas";
        $head['usernm'] = $this->aauth->get_user()->username;
        $data['accounts'] = $this->transactions_model->acc_list();
        $this->load->view('fixed/header', $head);
        $this->load->view('facturas_electronicas/borrar_facturas',$data);
        $this->load->view('fixed/footer');       
    }
    public function lista_facturas_a_borrar(){
        $numero_total;
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        $dateTime=new DateTime($_POST['sdate']);
        
        
        $api = new SiigoAPI();
        $api->getAuth(1);
        $api->getAuth2(2);
        $_SESSION['api_siigo']=$api;
        $_SESSION['errores']=array();

        $customers_t = $this->db->query("select id_invoice from filas_a_borrar where borrado!=1 ")->result_array();//and id=8241
        $numero_total=count($customers_t);
        $usuarios_restantes_lista = $customers_t;
        $array_return =array("total_usuarios"=>$numero_total,"lista_usuarios_a_facturar"=>$usuarios_restantes_lista);
        echo json_encode($array_return);
    }
    public function procesar_invoice_a_borrar(){
        $dateTime=new DateTime($_POST['sdate']);
        $caja1=$this->db->get_where('accounts',array('id' =>$_POST['pay_acc']))->row();
        
            //var_dump($_POST['id_inv']);
            $ret=$this->borrar_factura($_POST['id_inv'],$dateTime->format("Y-m-d"));
            $retorno["estado"]="procesado";
            $retorno["idv"]=$_POST['id_inv'];
            echo json_encode($retorno);
        
        
        

    }
     public function borrar_factura($id_invoice,$sdate){
        set_time_limit(150);
        ini_set ( 'max_execution_time', 150);
        ini_set ( 'max_execution_time', 150);
        var_dump($id_invoice);
         $_SESSION['api_siigo']->deleteInvoice($id_invoice);
                $this->db->update('filas_a_borrar',array("borrado"=>1),array("id_invoice"=>$id_invoice)); 
            
    }
}