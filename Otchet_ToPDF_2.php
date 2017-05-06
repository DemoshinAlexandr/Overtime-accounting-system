<?php  
//Разработана программа для отчета по сверхурочкам в формате pdf

include("../connect/my_index.php");					
require_once '../classes/tcpdf/tcpdf.php';  // подключение библиотеки для работы с pdf

$k = 0;    // количество сотрудников в таблице отчета
$kjAll = 0;   // количество всех строчек таблицы
$kjPage = 0;   // количество строчек таблицы в текущей странице документа
$Page = 1;
session_start();
		
$pdf = new TCPDF ('P', 'mm', 'A4', true, 'UTF-8', false); //Создание объекта PDF, установка его свойств
$pdf -> setPrintHeader (false);
$pdf -> setPrintFooter (true);

Function GetNameMonth ($Month0) {
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
return $Month;
}

// если пришли данные
if (isset($_GET['otd']) and $_GET['otd'] <> 0 and $_GET['otd'] <> 10000 and isset($_GET['dateStart']) and $_GET['dateStart'] <> '' and isset($_GET['dateFinish']) and $_GET['dateFinish'] <> '' and isset ($_SESSION['id_sot']))                      
{
   $id_aut = $_SESSION['id_sot'];
   $id_otd = $_GET['otd'];
   $dateSt = $_GET['dateStart'];
   $dateFn = $_GET['dateFinish'];
   $YearSt=mb_substr($dateSt,0,4,'UTF-8');
   $DaySt=mb_substr($dateSt,8,2,'UTF-8');
   $MonthSt = GetNameMonth(mb_substr($dateSt,5,2,'UTF-8'));
   $YearFn=mb_substr($dateFn,0,4,'UTF-8');
   $DayFn=mb_substr($dateFn,8,2,'UTF-8');
   $MonthFn = GEtNameMonth(mb_substr($dateFn,5,2,'UTF-8'));

   // начало заполнения пдфки, добавление первой странички
   $pdf -> AddPage();
  
   $Author = mysql_query("Select Fam, LEFT (Imya,1) Imya, LEFT(Otch,1) Otch, TabN From Sotrydniki Where id_sot = ".$id_aut." ");
   $DT = mysql_query("SELECT CURDATE() as date, CURTIME() as time");

	// содание подписи
	if ($row5 = mysql_fetch_array($Author) and $row3 = mysql_fetch_array($DT)) {
		$DateTime = $row3['date'].' '.$row3['time'];
		$By = 'Создано:'.date("d-m-Y", strtotime($DateTime)).' '.$row5['Fam'].' '.$row5['Imya'].' '.$row5['Otch'].'. таб.№'.$row5['TabN'];
		$Podpis =$By.' , распечатано из КИС "КЭТЗ"';
	  
		$pdf -> SetXY(110, 268);
		$pdf -> SetFont('times', '', 10);					
		$pdf -> MultiCell(90, 0, $Podpis, 0, 'R');
    }

  // получение названия отдела и суммы количества часов и минут сверхурочек за определенное время
	$All = mysql_query("SELECT Number_otd, Name_otd, Sum(Hour(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
	Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)) as Hours,
	Sum(Minute(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
	Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)) as Minutes,
	IfNull(Sum(Second(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
	Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)), 0) as Seconds
	From Overtime O Left Join Sotrydniki S on O.Employee = S.id_sot Left Join Otdel Ot on S.Otdel_id = Ot.id_otd
	Where S.Otdel_id = ".$id_otd." 
	and WorkOvertimeEndDate is not Null 
	and WorkOvertimeStartDate is not Null 
	and SyncFlagPerco = 1
	and Deleted = 0
	and WorkOvertimeStartDate between '".$dateSt."' and '".$dateFn."' 
	group by Number_otd, Name_otd"); 

	if ($row7 = mysql_fetch_array($All)) {
		$Min = ($row7['Minutes'] + (int)($row7['Seconds'] / 60)); // общее количество минут вместе с округленными секундами 
		$Hour = $row7['Hours'] + (int)($Min / 60);  // общее количество часов вместе с округленными минутами
		$Min = $Min % 60;  // остаток минут после округления
		$NumberOtd = $row7['Number_otd'];
		$NameOtd = $row7['Name_otd'];
		
		$pdf -> Image('ketz.jpeg', 10, 10, 27, 24);   
		$pdf -> SetXY (50, 5);
		$pdf -> SetFont('timesbd', '', 12);      

		// печать на разных строчках в зависимости от длины названия отдела		
		if ($NameOtd == "отдел Менеджмента эффективности производства и безопасности информационных технологий") {
			$pdf -> Cell(130, 15, 'Количество отработанных часов в 14600 (отдел Менеджмента', 0, 1, 'C' );       
			$pdf -> SetXY (50, 15);
			$pdf -> Cell(140, 15, 'эффективности производства и безопасности информационных технологий)', 0, 1, 'C');
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 25);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 35);
	     	$pdf -> Cell(120, 15, 'Общее время: '.$Hour.' часов '.$Min.' минут', 0, 1, 'C'); 
		}			
		elseif (strlen(trim($NameOtd)) > 45) {	
		    $pdf -> Cell(120, 15, 'Количество отработанных часов ', 0, 1, 'C' );       
			$pdf -> SetXY (50, 15);
		    $pdf -> Cell(120, 15, 'в '.$NumberOtd.' ('.$NameOtd.')', 0, 1, 'C');
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 25);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 35);
	     	$pdf -> Cell(120, 15, 'Общее время: '.$Hour.' часов '.$Min.' минут', 0, 1, 'C');  
		}
		else {
			$pdf -> Cell(120, 15, 'Количество отработанных часов в '.$NumberOtd.' ('.$NameOtd.')', 0, 1, 'C' );       
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 15);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 25);
	     	$pdf -> Cell(120, 15, 'Общее время: '.$Hour.' часов '.$Min.' минут', 0, 1, 'C');  
		}

    	$pdf -> SetFont('timesbd', '', 10); 			
    	$pdf -> SetXY (10, 55);	
		$pdf -> Cell(10, 7, '№', 1, 1, 'C'); 
        $pdf -> SetXY (20, 55);				
		$pdf -> Cell(50, 7, 'ФИО работника', 1, 1, 'L');
		$pdf -> SetXY (70, 55);				
		$pdf -> Cell(50, 7, 'Табельный номер', 1, 1, 'C');
		$pdf -> SetXY (120, 55);	
	    $pdf -> Cell(0, 7, 'Время', 1, 1, 'C'); 			
	
	
		// получение данных по сверхурочной работе для каждого сотрудника
		$FIOsumm = mysql_query ("SELECT Fam, LEFT (Imya,1) as Imya, LEFT (Otch,1) as Otch, TabN, Employee, 
			IfNull(Sum(Hour(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
			Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)),0) as Hours,
			IfNull(Sum(Minute(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
			Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)), 0) as Minutes,
			fNull(Sum(Second(Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime)
			Else TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)) End)), 0) as Seconds
			From Overtime O Left Join Sotrydniki S On O.Employee = S.id_sot 
            Left Join Otdel Ot on S.Otdel_id = Ot.id_otd
			Where WorkOvertimeEndDate is not Null 
			and WorkOvertimeStartDate is not Null 
			and WorkOvertimeStartDate between '".$dateSt."' and '".$dateFn."' 
			and SyncFlagPerco = 1 
			and Deleted = 0
			and Ot.id_otd = ".$id_otd."
			Group by Employee, Fam, Imya, Otch
			Order by Fam");

			while ($row = mysql_fetch_array($FIOsumm)) // цикл по всем работникам
			{
				$FIOMin = ($row['Minutes'] + (int)($row['Seconds'] / 60)); // общее количество минут вместе с округленными секундами 
				$FIOHour = $row['Hours'] + (int)($FIOMin / 60);  // общее количество часов вместе с округленными минутами
				$FIOMin = $FIOMin % 60;  // остаток минут после округления
				$FIO_work = $row['Fam'].' '.$row['Imya'].'.'.$row['Otch'].'.';
				$Tab = $row['TabN'];
	  
				// если есть сверхурочное время
				if ($FIOHour <> 0 or $FIOMin <> 0) {
					$k = $k + 1;  
					$kjAll = $kjAll + 1;  // общее количество всех строчек таблицы в отчете
					$kjPage = $kjPage + 1;  // количество строчек таблцицы для текущей страницы

					if ($kjAll == 30 or ($kjAll - 30) % 37 == 0) // если последняя строка текущей странички, создаем новую и добавляем подпись
					{
						$pdf -> AddPage();
						$kjPage = 0;
						$pdf -> SetXY(110, 268);
						$pdf -> SetFont('times', '', 10);					
						$pdf -> MultiCell(90, 0, $Podpis, 0, 'R');	  
					}	
		
					// если первая страничка или последующие		
					if ($kjAll < 30)  
						$Y = 55;
					Else 
						$Y = 5;
		
							$pdf -> SetFont('times', '', 10); 			
							$pdf -> SetXY (10, $Y + $kjPage * 7);	
							$pdf -> Cell(10, 7, $k, 1, 1, 'C'); 
							$pdf -> SetXY (20, $Y + $kjPage * 7);				
							$pdf -> Cell(50, 7, $FIO_work, 1, 1, 'L');
							$pdf -> SetXY (70, $Y + $kjPage * 7);				
							$pdf -> Cell(50, 7, $Tab, 1, 1, 'C');
							$pdf -> SetXY (120, $Y + $kjPage * 7);	
							$pdf -> Cell(0, 7, ' '.$FIOHour.':'.$FIOMin, 1, 1, 'C'); 	
			
							// данные сверхурочной работы по дням для текущего сотрудника
							$FIOdetail = mysql_query ("SELECT WorkOvertimeStartDate, WorkOvertimeEndDate,
                                        Case When WorkOvertimeEndDate = WorkOvertimeStartDate Then Left (TimeDiff (WorkOvertimeEndTime, WorkOvertimeStartTime), 5)
                                        Else Left (TimeDiff (TimeDiff('23:59:59', WorkOvertimeStartTime), TimeDiff('00:00:00', WorkOvertimeEndTime)), 5) End as Times
                                        From Overtime O Left Join Sotrydniki S On O.Employee = S.id_sot 
                                        Left Join Otdel Ot on S.Otdel_id = Ot.id_otd
                                        Where WorkOvertimeEndDate is not Null 
                                        and WorkOvertimeStartDate is not Null 
                                        and WorkOvertimeStartDate between '".$dateSt."' and '".$dateFn."' 
	                                    and SyncFlagPerco = 1 
	                                    and Deleted = 0
	                                    and O.Employee = ".$row['Employee']."
                                        Order by WorkOvertimeStartDate desc");
			
							While ($row2 = mysql_fetch_array($FIOdetail))	{
								$tim = $row2['Times'];
								$kjAll = $kjAll + 1;
								$kjPage = $kjPage + 1;
			
								if ($kjAll == 30 or  ($kjAll - 30) % 37 == 0) {
									$pdf -> AddPage();
									$kjPage = 0;
									$pdf -> SetXY(110, 268);
									$pdf -> SetFont('times', '', 9);					
									$pdf -> MultiCell(90, 0, $Podpis, 0, 'R');	
								}	
		  
								if ($kjAll < 30)  
									$Y = 55;
								Else 
									$Y = 5;
			
									$date = $row2['WorkOvertimeStartDate'];			
									$Year=mb_substr($date,0,4,'UTF-8');
									$Month=mb_substr($date,5,2,'UTF-8');
									$Day=mb_substr($date,8,2,'UTF-8');
									$date=" ".$Day.".".$Month.".".$Year;	
			
									if (mb_substr($tim, 0, 1, 'UTF-8') == '0'){
											$tim = mb_substr($tim, 1, 5, 'UTF-8');
									}
			
									$pdf -> SetFont('times', '', 10); 			
									$pdf -> SetXY (40, $Y + $kjPage * 7);				
									$pdf -> Cell(80, 7, $date, 1, 1, 'C'); 
									$pdf -> SetXY (120, $Y + $kjPage * 7);	
									$pdf -> Cell(0, 7, ' '.$tim, 1, 1, 'C'); 				  
							}			
				}	
			}    
	}
Else {
	$Am = mysql_query("SELECT Number_otd, Name_otd From Otdel
	Where Id_otd = ".$id_otd." ");
  
    While ($row11 = mysql_fetch_array($Am)) {
		$NumberOtd = $row11['Number_otd'];
		$NameOtd = $row11['Name_otd'];
		$YearSt=mb_substr($dateSt,0,4,'UTF-8');
		$DaySt=mb_substr($dateSt,8,2,'UTF-8');
		$MonthSt = GetNameMonth(mb_substr($dateSt,5,2,'UTF-8'));
		$YearFn=mb_substr($dateFn,0,4,'UTF-8');
		$DayFn=mb_substr($dateFn,8,2,'UTF-8');
		$MonthFn = GEtNameMonth(mb_substr($dateFn,5,2,'UTF-8'));
		
		$pdf -> Image('ketz.jpeg', 10, 10, 27, 24);  
		$pdf -> SetFont('timesbd', '', 12); 	
		$pdf -> SetXY (50, 5);
	
		if ($NameOtd == "отдел Менеджмента эффективности производства и безопасности информационных технологий") {
			$pdf -> Cell(130, 15, 'Количество отработанных часов в 14600 (отдел Менеджмента', 0, 1, 'C' );       
			$pdf -> SetXY (50, 15);
		    $pdf -> Cell(140, 15, 'эффективности производства и безопасности информационных технологий)', 0, 1, 'C');
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 25);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 35);
	     	$pdf -> Cell(120, 15, 'Отработанных часов нет', 0, 1, 'C');  
		}
		elseif (strlen(trim($NameOtd)) > 45) {	
		    $pdf -> Cell(120, 15, 'Количество отработанных часов в '.$NumberOtd, 0, 1, 'C' );       
			$pdf -> SetXY (50, 15);
		    $pdf -> Cell(120, 15, '('.$NameOtd.')', 0, 1, 'C');
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 25);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 35);
	     	$pdf -> Cell(120, 15, 'Отработанных часов нет', 0, 1, 'C');  
		}
		else {
			$pdf -> Cell(120, 15, 'Количество отработанных часов в '.$NumberOtd.' ('.$NameOtd.')', 0, 1, 'C' );       
			$pdf -> SetFont('times', '', 12); 
			$pdf -> SetXY (50, 15);
			$pdf -> Cell(120, 15, 'C '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn, 0, 1, 'C');
			$pdf -> SetXY (50, 25);
	     	$pdf -> Cell(120, 15, 'Отработанных часов нет', 0, 1, 'C');  
		}
    }
}	
$pdf -> Output ('Подробный отчет по сверхурочной работе '.$NumberOtd.' ('.$NameOtd.') '.' с '.$DaySt.' '.$MonthSt.' '.$YearSt.' по '.$DayFn.' '.$MonthFn.' '.$YearFn.'.pdf', "D");	
}

else  // если получены неверные данные
{
	echo "ERROR";
}
?>
