<?php  
//Разработана программа для печати сверхурочек в формате pdf

require_once '../connect/my_index.php';					
require_once '../classes/tcpdf/tcpdf.php';  // подключение библиотеки для работы с pdf
		
$pdf = new TCPDF ('P', 'mm', 'A4', true, 'UTF-8', false); //Создание объекта PDF, установка его свойств
$pdf -> setPrintHeader (false);
$pdf -> setPrintFooter (false);

$oneDay = true;
$pdfname = ""; 
$Er = 1;
$otdel_array = array('21' => "58", '18' => "54", '46' => "2", '52' => "58"); // массив из айди отделов, где в отделе нет начальника и выдается пусто: учебный центр, бухгалтерия, военное представительство, мини-гостиница КЭТЗ

if (isset($_GET['count'])) { //получена ли переменная count - количество распоряжений (она передается в случае, если печать всех сверхурочек). Дальше установка режима формы и количества сверурочек,                            
		$SingleMode = false;     //если клик по кнопке печать одной сверхурочки, то SingleMode = true, если печать для многих сверурочек то false
		$count = $_GET['count'];
		$array_id = explode('*', $_GET['id']);  
		$array_date = explode('*', $_GET['date']);
		$array_time = explode('*', $_GET['time']);
	}
else {                       // если не получена
		$SingleMode = true; 
		$count = 1;
	}
  
for ($j = 0; $j < $count; $j++)  // цикл по всем распоряжениям
{
    if (isset($_GET['id']) and isset($_GET['date']) and ($SingleMode == false)) 
		{	 
			$id = $array_id[$j];  // присвоение переменным значения элементов массива
			if ($j == 0)
			{$getDate = $array_date[$j];}
			else 
			{
				f ($getDate != $array_date[$j]) {$oneDay = false;} 
				$getDate = $array_date[$j];  
			}
			$getTime = $array_time[$j];
		}	
    Elseif (isset($_GET['id']) and isset($_GET['date']) and ($SingleMode == true)) 
		{
			$id = $_GET['id'];
			$getDate = $_GET['date'];
			$getTime = $_GET['time'];
		}
	
  
	if ($j % 2 == 0) {$pdf -> AddPage();}  // если счетчик цикла кратен 2, то создаем новую страничку, так как на одну страницу помещается распоряжения для двух сотрудников

    $res=mysql_query("SELECT * FROM sotrydniki WHERE (ID_SOT=".$id.") and (otdel_id<>21) and (otdel_id<>18) and (otdel_id<>46) and (otdel_id<>52)"); 
	 	
    if(mysql_num_rows($res)!=""){
	    $res1 = mysql_query("SELECT id_sot, Otdel_id, parent FROM sotrydniki As S1 Left Join otdel As O1 On S1.Otdel_id = O1.id_otd where (id_prof = 1) and (id_sot = ".$id.")"); 
        if(mysql_num_rows($res1)!="")	{   
			$row1 = mysql_fetch_array($res1);
            $SQL = "SELECT Overtime.ID AS ID, Overtime.second_change As Chang, Overtime.CreationDate As DateBy, Overtime.PaymentType AS type, ByWork.Fam AS FamBy,  LEFT(ByWork.Imya,1) AS ImyaBy, LEFT(ByWork.Otch,1) AS OtchBy, ByWork.TabN AS TabBy,
								Work.Fam AS Fam, LEFT(Work.Imya,1) AS Imya, LEFT(Work.Otch,1) AS Otch, Work.TabN AS TabN,
								Ot.Number_otd AS otdel,
								Nach.Fam AS NFam,LEFT(Nach.Imya,1) AS NImya, LEFT(Nach.Otch,1) AS NOtch
							FROM sotrydniki As Work
							INNER JOIN otdel AS Ot ON (Work.Otdel_id=Ot.Id_otd)                            
							INNER JOIN sotrydniki As Nach ON (Nach.Otdel_id=".$row1['parent'].")";
							
			if ($row1['parent'] == 13) { $SQL = $SQL." AND(Nach.id_prof = 1) AND (Nach.state=0)";}
			
			$SQL = $SQL."INNER JOIN overtime ON (overtime.Employee=".$id.") AND ((Case When Overtime.second_change = 0 then Overtime.WorkOvertimeStartDate When Overtime.second_change = 1 then Overtime.WorkOvertimeEndDate End)='".$getDate."')
							INNER JOIN sotrydniki As ByWork ON (Overtime.CreatedBy=ByWork.id_sot)
							WHERE (Overtime.deleted = 0) and (wORK.ID_SOT=".$id.")"; 
			$result = mysql_query($SQL);
		}
	    Else {
			$result = mysql_query("SELECT Overtime.ID AS ID, Overtime.second_change As Chang, Overtime.CreationDate As DateBy, Overtime.PaymentType AS type, ByWork.Fam AS FamBy,  LEFT(ByWork.Imya,1) AS ImyaBy, LEFT(ByWork.Otch,1) AS OtchBy, ByWork.TabN AS TabBy,
								Work.Fam AS Fam, LEFT(Work.Imya,1) AS Imya, LEFT(Work.Otch,1) AS Otch, Work.TabN AS TabN,
								Ot.Number_otd AS otdel,
								Nach.Fam AS NFam,LEFT(Nach.Imya,1) AS NImya, LEFT(Nach.Otch,1) AS NOtch
							FROM sotrydniki As Work
							INNER JOIN otdel AS Ot ON (Work.Otdel_id=Ot.Id_otd)
							INNER JOIN sotrydniki As Nach ON (Ot.Id_otd=Nach.Otdel_id) AND (Nach.Id_prof=1) AND (Nach.state=0)
							INNER JOIN overtime ON (overtime.Employee=".$id.") AND ((Case When Overtime.second_change = 0 then Overtime.WorkOvertimeStartDate When Overtime.second_change = 1 then Overtime.WorkOvertimeEndDate End)='".$getDate."')
							INNER JOIN sotrydniki As ByWork ON (Overtime.CreatedBy=ByWork.id_sot)
							WHERE (Overtime.deleted = 0) and (wORK.ID_SOT=".$id.")"); 	
	    }
	} 
	else { 
	    foreach ($otdel_array as $item => $description)
        {
	        $res=mysql_query("SELECT * FROM sotrydniki WHERE (ID_SOT=".$id.") and (otdel_id = ".$item.")");
		    if(mysql_num_rows($res)!="") 
		        $result = mysql_query("SELECT Overtime.ID AS ID, Overtime.second_change As Chang, Overtime.CreationDate As DateBy, Overtime.PaymentType AS type, ByWork.Fam AS FamBy,  LEFT(ByWork.Imya,1) AS ImyaBy, LEFT(ByWork.Otch,1) AS OtchBy, ByWork.TabN AS TabBy,
								Work.Fam AS Fam, LEFT(Work.Imya,1) AS Imya, LEFT(Work.Otch,1) AS Otch, Work.TabN AS TabN,
								Ot.Number_otd AS otdel,
								Nach.Fam AS NFam,LEFT(Nach.Imya,1) AS NImya, LEFT(Nach.Otch,1) AS NOtch
							FROM sotrydniki As Work
							INNER JOIN otdel AS Ot ON (Work.Otdel_id=Ot.Id_otd)
							INNER JOIN sotrydniki As Nach ON (Nach.Otdel_id=".$description.") 
							INNER JOIN overtime ON (overtime.Employee=".$id.") AND ((Case When Overtime.second_change = 0 then Overtime.WorkOvertimeStartDate When Overtime.second_change = 1 then Overtime.WorkOvertimeEndDate End)='".$getDate."')
							INNER JOIN sotrydniki As ByWork ON (Overtime.CreatedBy=ByWork.id_sot)
							WHERE (Overtime.deleted = 0) and (wORK.ID_SOT=".$id.")"); 
		}
	}	 
	while ($row = mysql_fetch_array($result)) {
	    $Er = 0;
		$change = $row['Chang'];
		$number_otdel = $row['otdel'];
		$FIO_nach = $row['NFam'].' '.$row['NImya'].'.'.$row['NOtch'];	
		$FIO_work = $row['Fam'].' '.$row['Imya'].'.'.$row['Otch'];	
		$TabNum = $row['TabN'];
		$c = $_GET['id'];
		$FAM=$row['Fam'];
		$ID=$row['ID'];
		$By = 'Создано:'.date("d-m-Y", strtotime($row['DateBy'])).' '.$row['FamBy'].' '.$row['ImyaBy'].'.'.$row['OtchBy'].'. таб.№'.$row['TabBy'];
		$Podpis =$By.' , распечатано из КИС "КЭТЗ"';
		
		if ($row['type']==1) {$type="оплату";} else {$type="отгул";}
		
		$time=$getTime;//время
		$timeH=mb_substr($time,0,2);
		$timeM=mb_substr($time,3,2);			
		$time=str_replace(".",":",$time);
		$time=$time.":00";
			
		$date=$getDate;//дата
		$Year=mb_substr($date,0,4,'UTF-8');//переменная, нач. индекс, количество знаков, кодировка
		$Month0=mb_substr($date,5,2,'UTF-8');
		$Day=mb_substr($date,8,2,'UTF-8');
				
		if($Month0==01) {$Month='января';}
		elseif($Month0==02) {$Month='февраля';}
		elseif($Month0==03) {$Month='марта';}
		elseif($Month0==04) {$Month='апреля';}
		elseif($Month0==05) {$Month='мая';}
		elseif($Month0==06) {$Month='июня';}
		elseif($Month0==07) {$Month='июля';}
		elseif($Month0=='08') {$Month='августа';} 
		elseif($Month0=='09') {$Month='сентября';}
		elseif($Month0==10) {$Month='октября';}
		elseif($Month0==11) {$Month='ноября';}
		elseif($Month0==12) {$Month='декабря';}

		$date=$Day." ".$Month." ".$Year."г";			
		
			// создания распоряжения
		if ($j % 2 == 0) {$pdf -> SetXY (0, 5);} else {$pdf -> SetXY (0, 140);}	  	
			// установка шрифтов
			$pdf -> SetFont('timesbd', '', 11);         
				// создание ячеек (весь документ состоит из ячеек, но без границ)		
				$pdf -> Cell(90, 15, '            РАСПОРЯЖЕНИЕ № '.$ID, 0, 1, 'C');       
			$pdf -> SetFont('times', '', 11); 
				$pdf -> Cell(30, 0, 'НАЧАЛЬНИКА', 0, 0, 'L');
				$pdf -> Cell(35, 0, $FIO_nach, 'B', 0, 'C');  
				$pdf -> Cell(25, 0, '№'.$number_otdel, 'B', 1, 'C');  
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(90, 0, 'цеха, отдела ', 0, 1, 'R');      
			$pdf -> SetFont('times', '', 11);
				$pdf -> Cell(50, 0, $FIO_work, 'B', 0, 'C');
				$pdf -> Cell(20, 0, 'таб.№', 0, 0, 'C');
				$pdf -> Cell(20, 0, $TabNum, 'B', 1, 'C');
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(50, 0, 'Ф.И.О. работника', 0, 1, 'C');
				$pdf -> Cell(90, 0, '', 0, 1, 'C');			
			$pdf -> SetFont('times', '', 10);
				$pdf -> Cell(90, 0, 'Поручается сверхурочная работа в связи', 0, 1, 'L');
				$pdf -> Cell(90, 0, 'с производственной необходимостью', 0, 1, 'L');
				$pdf -> Cell(90, 0, $date, 0, 1, 'C');
				$pdf -> Cell(90, 0, '', 0, 1, 'C');
				$pdf -> Cell(90, 0, 'На сверхурочную работу согласен за '.$type, 0 , 1, 'L');
				$pdf -> Cell(60, 0, '', 0, 0, 'C');
				$pdf -> Cell(30, 0, '', 0, 1, 'C'); 
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(60, 0, '', 0, 0, 'C');
				$pdf -> Cell(30, 0, 'подпись', 'T', 1, 'C'); 	
				$pdf -> Cell(90, 0, '', 0, 1, 'C');
			$pdf -> SetFont('times', '', 10);
		
		if ($change == 0){
		    $pdf -> Cell(30, 0, 'Начало работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, $timeH, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, $timeM, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		    $pdf -> Cell(30, 0, 'Конец работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		}
		Elseif ($change == 1) {
		    $pdf -> Cell(30, 0, 'Начало работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		    $pdf -> Cell(30, 0, 'Конец работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, $timeH, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, $timeM,  'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		}
		
			$pdf -> Cell(28, 0, 'Начальник', 0, 0, 'L');
			$pdf -> Cell(22, 0, $number_otdel, 'B', 0, 'C');
			$pdf -> Cell(10, 0, '', 0, 0, 'C');
			$pdf -> Cell(30, 0, '', 'B', 1, 'C');
		$pdf -> SetFont('times', '', 8);		
            $pdf -> Cell(54, 0, 'цеха, отдела  ', 0, 0, 'R');
            $pdf -> Cell(36, 0, 'подпись     ', 0, 1, 'R');		
            $pdf -> Cell(90, 0, '', 0, 1, 'R');
        $pdf -> SetFont('times', '', 10);
            $pdf -> Cell(60, 0, 'Контролер КПП', 0, 0, 'L');
            $pdf -> Cell(30, 0, '', 'B', 1, 'L');
		$pdf -> SetFont('times', '', 8);			
            $pdf -> Cell(90, 0, 'подпись     ', 0, 1, 'R');	
        $pdf -> SetFont('timesbd', 'B', 9);			
            $pdf -> MultiCell(90, 0, $Podpis, 0, 'R');	

		// создание корешка распоряжения			
		if ($j % 2 == 0) {$Y = 5; $pdf -> SetXY (110, $Y);} else {$Y = 140; $pdf -> SetXY (110, $Y);}	
		$pdf -> SetFont('timesbd', '', 11);
		    $pdf -> Cell(90, 15, 'КОРЕШОК РАСПОРЯЖЕНИЯ № '.$ID, 0, 2, 'C');
		$pdf -> SetFont('times', '', 11);
		    $pdf -> Cell(30, 0, 'НАЧАЛЬНИКА', 0, 0, 'L');
	        $pdf -> Cell(35, 0, $FIO_nach, 'B', 0, 'C');  
		    $pdf -> Cell(25, 0, '№'.$number_otdel, 'B', 0, 'C');  
		// установка координат
		$pdf -> SetXY (110, $Y + 20);
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(90, 0, 'цеха, отдела ', 0, 2, 'R');      
			$pdf -> SetFont('times', '', 11);
				$pdf -> Cell(50, 0, $FIO_work, 'B', 0, 'C');
				$pdf -> Cell(20, 0, 'таб.№', 0, 0, 'C');
				$pdf -> Cell(20, 0, $TabNum, 'B', 0, 'C');
		$pdf -> SetXY (110, $Y + 28.5);
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(50, 0, 'Ф.И.О. работника', 0, 2, 'C');
				$pdf -> Cell(90, 0, '', 0, 2, 'C');			
			$pdf -> SetFont('times', '', 10);
				$pdf -> Cell(90, 8, 'Отработал сверхурочно', 0, 2, 'L');
				$pdf -> Cell(90, 0, $date, 0, 2, 'C');
				$pdf -> Cell(90, 0, '', 0, 2, 'C');
				$pdf -> Cell(90, 0, 'На сверхурочную работу согласен за '.$type, 0 , 0, 'L');
				$pdf -> Cell(60, 0, '', 0, 0, 'C');
				$pdf -> Cell(30, 0, '', 0, 2, 'C');  // подпись
		$pdf -> SetXY (110, $Y + 62);
			$pdf -> SetFont('times', '', 8);
				$pdf -> Cell(60, 0, '', 0, 0, 'C');
				$pdf -> Cell(30, 0, 'подпись', 'T', 2, 'C');  // подпись	
				$pdf -> Cell(90, 0, '', 0, 0, 'C');
		$pdf -> SetXY (110, $Y + 68);
			$pdf -> SetFont('times', '', 10);
		
		if ($change == 0) {
		    $pdf -> Cell(30, 0, 'Начало работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, $timeH, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, $timeM, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 2, 'C');
			$pdf -> Cell(90, 0, '', 0, 0, 'C');
		$pdf -> SetXY (110, $Y + 78);
		    $pdf -> Cell(30, 0, 'Конец работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 2, 'C');
			$pdf -> Cell(90, 0, '', 0, 2, 'C');
		}
		Elseif ($change == 1) {
		    $pdf -> Cell(30, 0, 'Начало работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, '', 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		$pdf -> SetXY (110, $Y + 78);			
		    $pdf -> Cell(30, 0, 'Конец работы', 0, 0, 'L');
			$pdf -> Cell(15, 0, $timeH, 'B', 0, 'C');
			$pdf -> Cell(15, 0, 'час.', 0, 0, 'C');
			$pdf -> Cell(15, 0, $timeM,  'B', 0, 'C');
			$pdf -> Cell(15, 0, 'мин.', 0, 1, 'C');
			$pdf -> Cell(90, 0, '', 0, 1, 'C');
		}
		
		$pdf -> SetXY (110, $Y + 87);
			$pdf -> Cell(28, 0, 'Начальник', 0, 0, 'L');
			$pdf -> Cell(22, 0, $number_otdel, 'B', 0, 'C');
			$pdf -> Cell(10, 0, '', 0, 0, 'C');
			$pdf -> Cell(30, 0, '', 'B', 0, 'C');
		$pdf -> SetXY (110, $Y + 92);
		$pdf -> SetFont('times', '', 8);		
            $pdf -> Cell(54, 0, 'цеха, отдела  ', 0, 0, 'R');
            $pdf -> Cell(36, 0, 'подпись     ', 0, 2, 'R');		
            $pdf -> Cell(90, 0, '', 0, 0, 'R');
		$pdf -> SetXY (110, $Y + 98);
        $pdf -> SetFont('times', '', 10);		
            $pdf -> Cell(60, 0, 'Контролер КПП', 0, 0, 'L');
            $pdf -> Cell(30, 0, '', 'B', 1, 'L');
		$pdf -> SetXY (110, $Y + 103);
		$pdf -> SetFont('times', '', 8);			
            $pdf -> Cell(90, 0, 'подпись     ', 0, 2, 'R');	
		$pdf -> SetXY (110, $Y + 107);
        $pdf -> SetFont('timesbd', '', 9);			
            $pdf -> MultiCell(90, 0, $Podpis, 0, 'R');	
		$pdf -> SetXY (0,130);			
        $pdf -> Cell(500, 1, '', 'B');
		
    if ($count > 1) {
		$pdf -> SetXY (104.5, 0);
	    $pdf -> Cell(1, 134, '', 'R');
		if (($j != $count - 1) and ($j % 2 != 1)) {
	        $pdf -> SetXY (104.5, 134);
	        $pdf -> Cell(1, 142, '', 'R');
        }			
	    $pdfname = $pdfname.$FAM.'_';
	}
	else {
		$pdf -> SetXY (104.5, 0);
	        $pdf -> Cell(1, 134, '', 'R');
	    $pdfname = $FAM.'-'.$date;
	}
	}
	
} // конец цикла

if ($Er == 1) Echo "Error";
else {
  if ($count > 1) 
       {if ($oneDay == false) 
	         {if (rsort($array_date)) $pdfname = 'От '.$array_date[count($array_date)-2].' до '.$array_date[0].' '.$pdfname;} 
		elseif ($oneDay == true)  {if (rsort($array_date)) $pdfname = $date.' '.$pdfname;}
	   }
  
  $pdf -> Output ($pdfname.'.pdf', "D");		
} 
?>
