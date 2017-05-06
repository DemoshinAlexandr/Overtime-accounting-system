<?php
require_once("..\connect\connect_index.php");
mysql_query("SET NAMES 'utf8'");
 
$array_id = array();  // три массива для хранения в них айди сотрудника, даты и времени начала сверхурочки
$array_date = array();
$array_time = array();

function ConvertStr ($array_id) { // Функция превращения массивов айди, даты, времени в строку с разделителем *, нужно для передачи строки между файлами методом GET 
	$str = "";
	for ($i = 0; $i < count($array_id); $i++){
		$str = $str.$array_id[$i]."*";
	} 
	return $str;
}

//ajax для сохранения в БД
if(isset($_POST['RezId']) && isset($_POST['DateGo']) && isset($_POST['TimeGo']) && isset($_POST['Author'])) {
	$result = mysql_query("UPDATE overtime SET ProcessedDate='".$_POST['DateGo']."',ProcessedTime='".$_POST['TimeGo']."',  LastUpdatedBy=".$_POST['Author']." WHERE Id=".$_POST['RezId']."");			 
	if ($result) { 
		print 1;
	}
	else {	
		print 2;
	}									 
	exit();
}


if(isset($_POST['RezId']) && isset($_POST['RezPay'])&& isset($_POST['TimeStart1']) && isset($_POST['Sc']) && isset($_POST['Author'])) {
	if ($_POST['Sc'] == 0) {
		$result = mysql_query(
		"UPDATE overtime SET PaymentType=".$_POST['RezPay'].", WorkOvertimeStartTime = '".$_POST['TimeStart1']."', `LastUpdateDate` = Now(), `LastUpdatedBy`=".$_POST['Author']."  WHERE Id=".$_POST['RezId']."");
	}
	else {		
		$result = mysql_query(
		"UPDATE overtime SET PaymentType=".$_POST['RezPay'].", WorkOvertimeEndTime = '".$_POST['TimeStart1']."', `LastUpdateDate` = Now(), `LastUpdatedBy`=".$_POST['Author']."  WHERE Id=".$_POST['RezId']."");
    }
	if ($result) {
		print 1;
	}
	else {
		print 2;
	}									 
	exit();
}


if(isset($_POST['RezId']) && isset($_POST['TimeStart']) && isset($_POST['DateEnd'])&& isset($_POST['TimeEnd']) && isset($_POST['Sc']))
{
 if ($_POST['Sc'] == 0)
		$result = mysql_query("UPDATE overtime SET WorkOvertimeStartTime='".$_POST['TimeStart']."', WorkOvertimeEndDate='".$_POST['DateEnd']."', WorkOvertimeEndTime = '".$_POST['TimeEnd']."', SyncFlagPerco = 1 WHERE Id=".$_POST['RezId']."");
 else 
    	$result = mysql_query("UPDATE overtime SET WorkOvertimeEndTime='".$_POST['TimeEnd']."', WorkOvertimeStartDate='".$_POST['DateEnd']."', WorkOvertimeStartTime = '".$_POST['TimeStart']."', SyncFlagPerco = 1 WHERE Id=".$_POST['RezId']."");
     
		if ($result) { print 1;}
		else {print 2;}
									 
	exit();
}


if(isset($_POST['id']) && isset($_POST['del'])) {
	$result = mysql_query("UPDATE overtime SET deleted=1, LastUpdatedBy=".$_POST['Author']."  WHERE Id=".$_POST['id']."");
}

if(isset($_GET['dateStart'])) {
	$url="?dateStart=".$_GET['dateStart']."&dateFinish=".$_GET['dateFinish']."&OUT=".$_GET['OUT']."&pay=".$_GET['pay']."&otd=".$_GET['otd']."&work=".$_GET['work']."&go_sel=Просмотреть";
}
 ?>

<html>
	<head>
		<title>Просмотр выписанных сверхурочек</title>
		<script src="../js/jquery.js"></script>
		<script src="../js/bootstrap.js"></script>
		<link rel="stylesheet" type="text/css" href="../css/bootstrap.css">
	</head>
	<body  bgcolor=""> 
	<form>
		<center>
		<img src="../img/time-logo.png" />
		<hr size="3" />
		<a href="../main_trud.php">На главную</a>&nbsp;&nbsp;&nbsp;
		<a href="time_list.php">Выписать распоряжение</a>&nbsp;&nbsp;&nbsp;
		<a  href="../logout.php">Выйти из системы</a>
		<br /> <br />
	 
		<font size="5"><center>Просмотр выписанных распоряжений</center></font> <br />
		<?php
		session_start();
		if($_SESSION['autorized'] == true)
		{
			if($_SESSION['nach'] == 1 || $_SESSION['overtime'] == 1 || $_SESSION['admin'] == 1)
			{			
				if(isset($_REQUEST['go_sel']))
				{
					$a=$_GET['dateStart'];
					$b=$_GET['dateFinish'];
				}
				else
				{
					$a=new DateTime(date("Y-m-d"));
					$a->modify("-".date('N')." day");
					$a=$a->format("Y-m-d");
					$b=date("Y-m-d");
				}
		?>
		<form>    
		<table  border="0">
        <tr>		 
            <td align="center">
               c <input type='date' id='dateStart' name='dateStart' value="<?php echo $a;?>"> 
            </td>
            <td align="center">
               по <input type='date' id='dateFinish' name='dateFinish' value="<?php echo $b;?>"> 
            </td>
			<td>
				<select name="OUT" id="OUT">
					<option value='0'>тип выхода</option>
					<option value='1'>сверхурочно</option>
					<option value='2'>выходной</option>
				</select>
			</td>
			<td>
				<select name="pay" id="pay">
					<option value='0'>тип оплаты</option>
					<option value='1'>оплата</option>
					<option value='2'>за отгул</option>
				</select>
			</td>
            <td>
            <?php			
					if($_SESSION['nach'] == 1 || ($_SESSION['overtime'] == 1 and $_SESSION['accountant_salary'] == 0 and $_SESSION['timekeeper'] == 0 ))
					{
						if ($_SESSION['Otdel_id'] == 13)
						{
							$sql_ss = "SELECT * FROM otdel WHERE id_otd = ".$_SESSION['Otdel_id']." OR id_otd = 74 OR id_otd = 75 OR id_otd = 76 OR id_otd = 77  ORDER BY  Number_otd";
						}
						else
						{
							$sql_ss = "SELECT * FROM otdel WHERE id_otd = ".$_SESSION['Otdel_id']." ORDER BY Number_otd";
						}
					}
					if($_SESSION['admin'] == 1 || $_SESSION['accountant_salary'] == 1 || $_SESSION['timekeeper'] == 1)
					{
						$sql_ss = "SELECT * FROM otdel WHERE Number_otd <> 10000 ORDER BY Number_otd";
					}
					$result = mysql_query($sql_ss);
					if(mysql_num_rows($result)!="")
					{
						if(isset($_REQUEST['go_sel']))
						{
							if($_REQUEST['otd'] > 0)
							{
								$selectet_otd = $_REQUEST['otd'];
							}
							else
							{
								$selectet_otd = $_SESSION['Otdel_id'];
							}
						}
						else
						{
							$selectet_otd = $_SESSION['Otdel_id'];
						}
						
						echo '<select name="otd" id="otd"> ';
						echo '<option value="0">Выберите отдел *</option>';
						if($_SESSION['admin'] == 1 || $_SESSION['buh'] == 1) echo '<option value="10000">10000 Дирекция</option>';
						for($i = 0; $i < mysql_num_rows($result); $i++)
						{
							$res = mysql_fetch_array($result);
							echo '<option value="'.$res['id_otd'].'"';
							if($res['id_otd'] == $selectet_otd) echo " selected=selected";  //если в сессии совпадает выбираем по умолчанию
							echo '>'.$res['Number_otd'].' '.$res['Name_otd'].'</option>';
						}
						echo '</select>';
					}
					if(isset($_GET['otd']))
					{
						$res_sot = mysql_query("SELECT Id_sot, Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch FROM sotrydniki WHERE (Otdel_id = ".$_GET['otd'].") and state in (0,3)  ORDER BY Fam");
						echo '<select name="work" id="work" onchange="onMyChange()"> ';
						echo '<option value="0">Выберите сотрудников </option>';;
						for($i = 0; $i < mysql_num_rows($res_sot); $i++)
						{
							$res = mysql_fetch_array($res_sot);
							echo '<option value="'.$res['Id_sot'].'">'.$res['Fam'].' '.$res['Imya'].'.'.$res['Otch'].'.</option>';
						}
						echo '</select>';
					}
					else
					{
						$res_sot = mysql_query("SELECT Id_sot, Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch FROM sotrydniki WHERE (Otdel_id = ".$_SESSION['Otdel_id'].") and state in (0,3)  ORDER BY Fam");
						echo '<select name="work" id="work" onchange="onMyChange()"> ';
						echo '<option value="0">Выберите сотрудников </option>';;
						for($i = 0; $i < mysql_num_rows($res_sot); $i++)
						{
							$res = mysql_fetch_array($res_sot);
							echo '<option value="'.$res['Id_sot'].'">'.$res['Fam'].' '.$res['Imya'].'.'.$res['Otch'].'.</option>';
						}
						echo '</select>';
					}
                ?>				
            </td>
            <td align="center" >
				<input type="submit" name="go_sel" value="Просмотреть" />
            </td>
			</tr>
		</table>
		</form>   
		<!-- Основная часть -->
		<?php       
			//СТРОКА С ВЫБОРОМ ПАРАМЕТРОВ
			echo '</br><table class="table table-bordered"><tr class="info"><td>№ распоряжения</td>
																			<td>ФИО</td>
																			<td>Табельный номер</td>
																			<td colspan=2>Тип</td>
																			<td colspan=1 hidden>Смена</td> 
																			<td colspan=2>Начало сверхурочной работы</td>
																			<td colspan=2>Конец сверхурочной работы</td>
																			<td>Дата расчета / отгула</td>
																			<td>Комментарий к работе</td> '
																		;
			$otdel = true;															
			if(isset($_REQUEST['go_sel'])) //ЕСЛИ ПРИШЛИ ПАРАМЕТРЫ, ДЕЛАЕМ SQL
			{
				if ((isset($_GET['dateStart'])) AND (isset($_GET['dateFinish']))) 
				{
					$queryTerms='';					
					if ((isset($_GET['otd'])) AND ($_GET['otd']>0))
					{
						if ((isset($_GET['OUT']))AND ($_GET['OUT']>0))
						{
						$queryTerms.='AND (OutputType='.$_GET['OUT'].')';
						}
						if ((isset($_GET['pay']))AND ($_GET['pay']>0))
						{
						$queryTerms.='AND (PaymentType='.$_GET['pay'].')';
						}
						if ((isset($_GET['work']))AND ($_GET['work']>0))
						{
						$queryTerms.='AND (Id_sot ='.$_GET['work'].')';
						}
						$result = mysql_query("SELECT ID, Employee,Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch, TabN, OutputType, PaymentType, (Case When second_change = 0 then IFNULL(WorkOvertimeStartDate,'') When second_change = 1 then IFNULL(WorkOvertimeEndDate,'') End) As OvertimeDateWork, 
													  (Case When second_change = 0 then IFNULL(WorkOvertimeStartTime,'') When second_change = 1 then IFNULL(WorkOvertimeEndTime,'') End) As OvertimeTimeWork,
													  IFNULL(WorkOvertimeEndDate,'') AS WorkOvertimeEndDate, IFNULL(WorkOvertimeEndTime,'') AS WorkOvertimeEndTime, IFNULL(WorkOvertimeStartDate,'') AS WorkOvertimeStartDate, WorkOvertimeStartTime, SyncFlagPerco, IFNULL(ProcessedDate,'') AS ProcessedDate,ProcessedTime, Comment
										FROM overtime, sotrydniki
										WHERE (Id_sot = Employee) AND (Otdel_id = ".$_GET['otd'].") ".$queryTerms." AND (Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End) BETWEEN '".$_GET['dateStart']."' and '".$_GET['dateFinish']."' and (overtime.deleted=0) 										
										ORDER BY (Case When second_change = 0 then IFNULL(WorkOvertimeStartDate,'') When second_change = 1 then IFNULL(WorkOvertimeEndDate,'') End) DESC");		
						$otdel = true;
					}
					else
					{
					//ECHO 'НЕ ВЫБРАН ОТДЕЛ';
					$otdel = false;
					}
				}
				else
				{ //если не пришли даты начала и конца, значит произошла ошика, достаточно просто вывести старый запрос
					$result = mysql_query("SELECT Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch, TabN, OutputType, PaymentType, second_change, (Case When second_change = 0 then IFNULL(WorkOvertimeStartDate,'') When second_change = 1 then IFNULL(WorkOvertimeEndDate,'') End) As OvertimeDateWork, 
													(Case When second_change = 0 then IFNULL(WorkOvertimeStartTime,'') When second_change = 1 then IFNULL(WorkOvertimeEndTime,'') End) As OvertimeTimeWork,
													  IFNULL(WorkOvertimeEndDate,'') AS WorkOvertimeEndDate, IFNULL(WorkOvertimeEndTime,'') AS WorkOvertimeEndTime, IFNULL(WorkOvertimeStartDate,'') AS WorkOvertimeStartDate, WorkOvertimeStartTime, SyncFlagPerco,IFNULL(ProcessedDate,'') AS ProcessedDate,ProcessedTime, Comment
									FROM overtime, sotrydniki
									WHERE (Id_sot = Employee) AND (Otdel_id = ".$_SESSION['Otdel_id'].") AND ((Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End)>".$a.") and (overtime.deleted=0) ORDER BY (Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End) DESC");
				}
				
			}
			elseif(isset($_GET['id_sot'])) //пришел id сотрудника
			{
				$result = mysql_query("SELECT ID, Employee,Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch, TabN, OutputType, PaymentType, second_change, (Case When second_change = 0 then IFNULL(WorkOvertimeStartDate,'') When second_change = 1 then IFNULL(WorkOvertimeEndDate,'') End) As OvertimeDateWork, 
													(Case When second_change = 0 then IFNULL(WorkOvertimeStartTime,'') When second_change = 1 then IFNULL(WorkOvertimeEndTime,'') End) As OvertimeTimeWork,
													  IFNULL(WorkOvertimeEndDate,'') AS WorkOvertimeEndDate, IFNULL(WorkOvertimeEndTime,'') AS WorkOvertimeEndTime, IFNULL(WorkOvertimeStartDate,'') AS WorkOvertimeStartDate, WorkOvertimeStartTime, SyncFlagPerco,IFNULL(ProcessedDate,'') AS ProcessedDate,ProcessedTime, Comment
									FROM overtime, sotrydniki
									WHERE (Id_sot = ".$_GET['id_sot'].") AND ((Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End) LIKE '".DATE('Y-m')."%') and (overtime.deleted=0) ORDER BY (Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End) DESC");
			}
			else //ЕСЛИ НЕ ПРИШЛИ ПАРАМЕТРЫ, В ЗАПРОСЕ ОТОБРАЖАЕМ ВСЕ РАСПОРЯЖЕНИЯ ПО ОТДЕЛУ АВТОРИЗОВАВШЕГОСЯ ПОЛЬЗОВАТЕЛЯ за эту неделю
			{
				$result = mysql_query("SELECT ID, Employee,Fam, LEFT(Imya,1) AS Imya, LEFT(Otch,1) AS Otch, TabN, OutputType, second_change, PaymentType, (Case When second_change = 0 then IFNULL(WorkOvertimeStartDate,'') When second_change = 1 then IFNULL(WorkOvertimeEndDate,'') End) As OvertimeDateWork, 
									(Case When second_change = 0 then IFNULL(WorkOvertimeStartTime,'') When second_change = 1 then IFNULL(WorkOvertimeEndTime,'') End) As OvertimeTimeWork,
													  IFNULL(WorkOvertimeEndDate,'') AS WorkOvertimeEndDate, IFNULL(WorkOvertimeEndTime,'') AS WorkOvertimeEndTime, IFNULL(WorkOvertimeStartDate,'') AS WorkOvertimeStartDate, WorkOvertimeStartTime, SyncFlagPerco,IFNULL(ProcessedDate,'') AS ProcessedDate,ProcessedTime, Comment
									FROM overtime, sotrydniki
									WHERE (Id_sot = Employee) AND (Otdel_id = ".$_SESSION['Otdel_id'].") AND ((Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End)>='".$a."') and (overtime.deleted=0) ORDER BY (Case When second_change = 0 then WorkOvertimeStartDate When second_change = 1 then WorkOvertimeEndDate End) DESC");
			}										
			
			if((mysql_num_rows($result)>0) and ($otdel == true))
			{
				while ($row = mysql_fetch_array($result)) //результаты каждой строки
				{   
					$array_id[] = $row['Employee'];
					$array_date[] = $row['OvertimeDateWork'];
					$array_time[] = $row['OvertimeTimeWork'];
				}  
																		
				echo '
					<td width="90" align="center">Печать<a href="time_ToPDF.php?id='.ConvertStr($array_id).'&count='.count($array_id).'&date='.ConvertStr($array_date).'&time='.ConvertStr($array_time).'"> 
					<img src="../img/PRINT.png" width="40" height="39" alt="" title="Распечатать все выбранные распоряжения" />
					</a>
					</td>
					<td><div class = "block4">Печать всех выбранных распоряжений</div></td>
                     <td>Редактировать</td></tr>';
			};
																				

			if((mysql_num_rows($result)>0))
			{
				while ($row = mysql_fetch_array($result)) //результаты каждой строки
				{ 
					echo '<tr>';					
					echo '<td>'.$row['ID'].'</td><td class="Fam">'.$row['Fam'].' '.$row['Imya'].'.'.$row['Otch'].'</td>';					
					echo'<td>'.$row['TabN'].'</td><td>';
						if ($row['OutputType']==1) {echo 'сверхурочно</td>';}
							else {echo 'выходной</td>';}
						echo '<td class="Pay Pay_'.$row['ID'].'">';	
						if ($row['PaymentType']==1) 
									{ echo '<img src="../img/coin.png" width="25" height="25" alt="" />';}
						else {								
								echo '<img src="../img/go.png" width="25" height="25" alt="" />';								
							}
						echo '</td>';
										
					// если сверхурочка дольше 6 часов, то дата окончания подсвечивается красным
					// получаем дату конца timeE и дату начала timeS
					$timeE = $row['WorkOvertimeEndTime'];
					$timeS = $row['WorkOvertimeStartTime'];
					
					// часы и минуты конца
					$timeH=mb_substr($timeE,0,2);
			        $timeM=mb_substr($timeE,3,2); 
					
					// если даты не равны, то есть дата конца сверхурочки это следующий день, то к времени конца прибавляем 24 часа
					if ($row['WorkOvertimeStartDate'] == $row['WorkOvertimeEndDate']) 
					    {$timeEnd = ($timeH * 60 + $timeM)/60;}
					else
					    {$timeEnd = ($timeH * 60 + $timeM)/60 + 24;}
					
					// часы и минуты начала
					$timeH=mb_substr($timeS,0,2);
			        $timeM=mb_substr($timeS,3,2);
					
					//время начала
					$timeStart = ($timeH * 60 + $timeM)/60;			
						
					echo 
					'<td class="Second_change" hidden>'.$row['second_change'].'</td>
				    <td class="DateOut DateOut_'.$row['ID'].'" name="DateOut">'.$row['WorkOvertimeStartDate'].'</td>
					<td class="TimeOut TimeOut_'.$row['ID'].'" name="TimeOut">'.$row['WorkOvertimeStartTime'].'</td>
					<td class="DateExit DateExit_'.$row['ID'].'" name="DateExit">'.$row['WorkOvertimeEndDate'].'</td>';
					
					// из времени конца вычитаем время начала, получаем десятичном виде количество часов между началом и концом. 						
					if ((int)$timeE > 0 and $timeEnd - $timeStart >= 6)
					{
					    echo 
					    '<td style = "background: #FF6347 " class="TimeExit TimeExit_'.$row['ID'].'" name="TimeExit">'.$row['WorkOvertimeEndTime'].'</td>';
					}
					else
					{
					    echo
					    '<td class="TimeExit TimeExit_'.$row['ID'].'" name="TimeExit">'.$row['WorkOvertimeEndTime'].'</td>';
					}
					echo'
					<td class="ProcessedDate_'.$row['ID'].'">';
					
					
					if ($row['second_change']==0)  // если первая смена
					{
						if (($row['ProcessedDate']=="") AND ($row['PaymentType']==2) and ($row['WorkOvertimeEndDate']<>'') 
						and ( ($_SESSION['admin'] == 1) OR ($_SESSION['accountant_salary'] == 1) OR ($_SESSION['timekeeper'] == 1)))
						{
							echo '<span class="id" style="visibility: hidden">'.$row['ID'].'</span>
									<a style="color: #000000; background: #00FFFF; padding: 2px; text-decoration: none; border-bottom-color: #b3b3b3; border-radius: 4px;" href="#myModal2" role="button" class="btn" data-toggle="modal">
									ИСПОЛЬЗОВАТЬ
									</a>';
						}
						else
						{
							echo	$row['ProcessedDate'];
						}
					}
					elseif ($row['second_change'] == 1) // если вторая смена
					{
						if (($row['ProcessedDate']=="") AND ($row['PaymentType']==2) and ($row['WorkOvertimeStartDate']<>'') 
						and ( ($_SESSION['admin'] == 1) OR ($_SESSION['accountant_salary'] == 1) OR ($_SESSION['timekeeper'] == 1)))
						{
							echo '<span class="id" style="visibility: hidden">'.$row['ID'].'</span>
									<a style="color: #000000; background: #00FFFF; padding: 2px; text-decoration: none; border-bottom-color: #b3b3b3; border-radius: 4px;" href="#myModal2" role="button" class="btn" data-toggle="modal">
									ИСПОЛЬЗОВАТЬ
									</a>';
						}
						else
						{
							echo	$row['ProcessedDate'];
						}
					}
					echo '</td>
							<td>'.$row['Comment'].'</td>
							<td><a href="time_ToPDF.php?id='.$row['Employee'].'&date='.$row['OvertimeDateWork'].'&time='.$row['OvertimeTimeWork'].'"> 
													<img src="../img/PRINT.png" width="30" height="30" alt="" title="Распечатать текущее распоряжение" />
							</a>
							</td>
							<td><div class="block3">Текущее распоряжение</div></td>
							<td class="Action_'.$row['ID'].'">';
					// код для редактирование сверурочки						
					$b = date("m"); // месяц текущей даты
					if ($row['second_change']==0) //если 1 смена
						{
							$a = substr($row['WorkOvertimeStartDate'],5,2) ; // месяц сверхурочки
								//если сегодня или  если она "закрыта" и выписана в этом месяце							
							if (
								(
								(($row['WorkOvertimeStartDate']>=date("Y-m-d")) )
								OR (($row['WorkOvertimeEndDate']<>'')) 
								OR (($row['WorkOvertimeEndDate']<>'') and ($_SESSION['admin'] == 1 OR $_SESSION['accountant_salary'] == 1 OR $_SESSION['timekeeper'] == 1) ) 
								) and ($row['ProcessedDate']=="")  //условие, что рапспоряжение ещё не обработано
								)
							{
								echo 
								"
								<span class='id' style='visibility: hidden'>".$row['ID']."</span>
								<a href='#myModal3' role='button' class='btn' data-toggle='modal'>
									<img src='../img/edit.png' width='30' height='30' alt='' />
								</a>
								";
							}
							
							elseif ((( $a <> $b ) and ($_SESSION['admin'] <> 1) and ($_SESSION['accountant_salary'] <> 1) and ($_SESSION['timekeeper'] <> 1)) or ($row['ProcessedDate']<>""))
							{
							
							}							
							else //не сегодня и нет конца. значит можно удалить
							{
								echo 
								"
								<span class='id' style='visibility: hidden'>".$row['ID']."</span>
								<a href='#myModal4' role='button' class='btn' data-toggle='modal'>
									<img src='../img/+no_small.png' width='30' height='30' alt='' />
								</a>
								";							
							}	
						}
						elseif ($row['second_change']==1) //если вторая смена
						{
							$a = substr($row['WorkOvertimeEndDate'],5,2) ; // месяц сверхурочки
								//если сегодня или  если она "закрыта" и выписана в этом месяце							
							if (
								(
								($row['WorkOvertimeEndDate']>=date("Y-m-d")) 
								OR (($row['WorkOvertimeStartDate']<>'') )
								OR (($row['WorkOvertimeStartDate']<>'') and ($_SESSION['admin'] == 1 OR $_SESSION['accountant_salary'] == 1 OR $_SESSION['timekeeper'] == 1) )
								)
								and ($row['ProcessedDate']=="")  //условие, что рапспоряжение ещё не обработано
								)
							{								
								echo 
								"
								<span class='id' style='visibility: hidden'>".$row['ID']."</span>
								<a href='#myModal3' role='button' class='btn' data-toggle='modal'>
									<img src='../img/edit.png' width='30' height='30' alt='' />
								</a>
								";								
							}							
							elseif ( ( $a <> $b ) and ($_SESSION['admin'] <> 1) and ($_SESSION['accountant_salary'] <> 1) and ($_SESSION['timekeeper'] <> 1))
							{
							
							}							
							else //не сегодня и нет начала. значит можно удалить							
							{
								echo 
								"
								<span class='id' style='visibility: hidden'>".$row['ID']."</span>
								<a href='#myModal4' role='button' class='btn' data-toggle='modal'>
									<img src='../img/+no_small.png' width='30' height='30' alt='' />
								</a>
								";							
							}
						}						
						
					echo'
					</td>
					</tr>';
				} 
			}
			echo '</table>';
		}
		else		
        {
			echo 'Нет прав на просмотр';
		}		

echo '
</center>
</form>
'
:
}
else
{
    echo '<center>Авторизируйтесь</center>';
    header('location:index.php');
}

?>

</body>
</html>

<div class="modal hide" id="myModal4"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form action="time_list_old.php <?php if (isset($url)) {echo $url;} else {} ?> " method="post" class="form_EditExit">  
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">Удалить распоряжение</h3>
		</div>
		<div class="modal-body" align="center">
			<table border=0>
				<tr>	
					<td> 
						<label class="control-label">ФИО: </label>
					</td>
					<td> 
						<input type='text' id='FIO' class='FIO' name='FIO' value='' disabled> 
					</td>
				</tr>
				<tr>	
					<td> 
						<label class="control-label">Дата: </label>
					</td>
					<td> 
						<input type='text' id='dateStart' class='dateStart' name='dateStart' value='' disabled> 
					</td>
				</tr>
				<?php
				if ($_SESSION['admin'] == 1 || $_SESSION['accountant_salary'] == 1) {
					echo'
					<tr>	
						<td> 
							<label class="control-label">Дата выхода: </label>
						</td>
						<td> 
							<input type="date" id="dateEnd" class="dateEnd" name="dateEnd"  value=""> 
						</td>
					</tr>
					<tr>	
						<td> 
							<label class="control-label">Время начала: </label>
						</td>
						<td> 
							<input type="time" id="timeStart" class="timeStart" name="timeStart" > 
						</td>
					</tr>
					<tr>	
						<td> 
							<label class="control-label">Время выхода: </label>
						</td>
						<td> 
							<input type="time" id="timeEnd" class="timeEnd" name="timeEnd"  value=""> 
						</td>
						</tr>';		
				}
				?>
			</table>
		</div>
		<div class="modal-footer">
			<input type="hidden" class="id" id='Id_doc' name="id">
			<input type="hidden" class="del" id='del' name="del" value="1">
			<input type="hidden" id='Author' name='Author' value='<?=$_SESSION['id_sot'];?>'>
			<?php
			if ($_SESSION['admin'] == 1 || $_SESSION['accountant_salary'] == 1) {
				echo'
					<button class="btn btn-primary alert alert-success btn_EditExit"  id="editExit" > Изменить </button>
				';		
			}
			?>
			<button class="btn btn-primary alert alert-error"  id="editDel" >Удалить</button>
		</div>
	</form> 
</div>	


<div class="modal hide" id="myModal3"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" onclick="a = 2">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel">Изменить распоряжение</h3>
	</div>
	<div class="modal-body" align="center">
	<table border=0>
		<tr>	
			<td> 
				<label class="control-label">ФИО: </label>
			</td>
			<td> 
				<input type='text' id='FIO' class='FIO' name='FIO' value='' disabled> 
			</td>
		</tr>
		<tr>	
			<td> 
				<label class="control-label">Дата: </label>
			</td>
			<td> 
				<input type='text' id='dateStart' class='dateStart' name='dateStart' value=''disabled> 
			</td>
		</tr>
		<tr>	
			<td> 
				<label class="control-label" onblur="alert(this.value)">Время начала сверхурочной работы: </label>
			</td>
			<td> 
				<input type='time' id='t2' class='t2' name='t2' > 
			</td>
		</tr>	 
		<tr>	
			<td>
				<input type="radio" name='type_pay' class='type_pay' value='1' >За оплату
			</td>
			<td>
				<input type="radio" name='type_pay' class='type_pay' value='2' checked >За отгул
			</td>
		</tr>
		</table>
	</div>
	<div class="modal-footer">
		<input type="hidden" class="id" id='Id_doc' name="id">
		<input type="hidden" id='Author' name='Author' value='<?=$_SESSION['id_sot'];?>'>
		<button class="btn btn-primary alert  alert-success " name='give' id='give' onclick="onEdit()">Изменить</button>
	</div>
</div>	


<div class="modal hide" id="myModal2"  role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel">Выберите дату отгула</h3>
	</div>
	<div class="modal-body" align="center">
		<label class="control-label">Дата отлучки: </label>
		<input type='date' id='dateGo' class='dateGo' name='dateGo' value="<?php echo date("Y-m-d");?>"> 
		</br>
		<label class="control-label">Время отлучки: </label>
		<input type="time" id='timeGo' class='timeGo' name='timeGo'  value="16:25"/>
	</div>
	<div class="modal-footer">
		<input type="hidden" class="id" id='Id_doc' name="id">
		<input type="hidden" id='Author' name='Author' value='<?=$_SESSION['id_sot'];?>'>
		<button class="btn btn-primary alert  alert-success " name='giveGo' id='giveGo' onclick="onGo()">Использовать</button>
	</div>
</div>	


<script> //скрипт для передачи в модальное окно номера сопроводительной и текущей операции
setTimeout(function() {
        $(function() {
            $(document).on('click', '[href*="#myModal"]', function() {
                var ths = $(this),	
					Fam = ths.closest('td').prevAll('.Fam').text();
					Window.Sc = ths.closest('td').prevAll('.Second_change').text();
					DateStartSU = ths.closest('td').prevAll('.DateOut').text();
					TimeStartSU = ths.closest('td').prevAll('.TimeOut').text();
					TimeStartSU1 = TimeStartSU;
					DateEndSU = ths.closest('td').prevAll('.DateExit').text();
					TimeEndSU = ths.closest('td').prevAll('.TimeExit').text(); 
                    id = ths.closest('td').find('.id').html();
                setTimeout(function() {
				if (Window.Sc == 1) {DateStartSU = DateEndSU; TimeStartSU = TimeEndSU;}
						$('.modal-body .FIO').val(Fam);
						$('.modal-body .dateStart').val(DateStartSU);
						$('.modal-body .dateEnd').val(DateStartSU);
						$('.modal-body .t2').val(TimeStartSU);
						$('.modal-body .timeStart').val(TimeStartSU1);
			            $('.modal-body .timeEnd').val(TimeEndSU);
						$('.modal-footer .id').val(id);
					}, 500);
				document.getElementById('NewSpan').remove();
				$('#give').show()
				$('#giveGo').show()
            });
		});
   }, 100);

function onEdit(){
	var RezId = document.getElementById('Id_doc').value;
	var TimeStart1 = document.getElementById('t2').value;
	var Sc = Window.Sc;
	var Pay_type = document.getElementsByName('type_pay')
	var Author = document.getElementById('Author').value;
	for(i=0; i < Pay_type.length; i++){
			if(Pay_type[i].checked) var RezPay = Pay_type[i].value
		}
	var data = {
		RezId:RezId,
		TimeStart1:TimeStart1,
		Sc:Sc,
		RezPay:RezPay,
		Author:Author
		};
	$.post('time_list_old.php',data)
		.done(
			function(data){
				if(data==1){ //если пришло 1, значит запрос к базе прошел
					//отображаем ссылку для распечатки корешка
					console.log(data)
					$("<span class='alert alert-info' id='NewSpan'>Изменена сверхурочка</span>").insertBefore('#give');
					$('#give').hide();
					if (RezPay==1) { 
						$a="<img src='../img/coin.png' width='25' height='25' alt='' />'"; 
						$b="";
					}
					else  { 
						$a="<img src='../img/go.png' width='25' height='25' alt='' />";
						$b='<span class="id" style="visibility: hidden">'+RezId+'</span><a style="color: #000000; background: #00FFFF; padding: 2px; text-decoration: none; border-bottom-color: #b3b3b3; border-radius: 4px;" href="#myModal2" role="button" class="btn" data-toggle="modal">	ИСПОЛЬЗОВАТЬ</a>';								
					}					
					$(".Pay_"+RezId+"").html($a);
					$(".ProcessedDate_"+RezId+"").html($b);
					if (Sc == 0)
					    $(".TimeOut_"+RezId+"").html(TimeStart1);
					else 
					    $(".TimeExit_"+RezId+"").html(TimeStart1);
				}
				else
				{
					alert(data+'Ошибка в SQL-запросе. Пожалуйста, попробуйте еще раз');		
				}
			}
		)
		.fail(function(data){
			alert('Ошибка при отправке данных на форму. Пожалуйста, попробуйте еще раз');
		}
		)		
	;}

function onGo(){
	var RezId = document.getElementById('Id_doc').value;
	var DateGo = document.getElementById('dateGo').value;
	var TimeGo = document.getElementById('timeGo').value;
	var Author = document.getElementById('Author').value;
	var data = {
		RezId:RezId, //id документа
		DateGo:DateGo, //дата отгула
		TimeGo:TimeGo,
		Author:Author
		};
	$.post('time_list_old.php',data)
		.done(
			function(data){
				if(data==1){ //если пришло 1, значит запрос к базе прошел
					$("<span class='alert alert-info' id='NewSpan'>Отгул зачтен</span>").insertBefore('#giveGo')
					$('#giveGo').hide();					
					$(".ProcessedDate_"+RezId+"").text(DateGo);
					$(".Pay_"+RezId+"").text("за отгул");
				}
				else 
				{
					alert('Ошибка в SQL-запросе. Пожалуйста, попробуйте еще раз');		
				}
			}
		)
		.fail(function(data){
			alert('Ошибка при отправке данных на форму. Пожалуйста, попробуйте еще раз');
		}
		)
	;}	

$(function() {		
			$(document).on('click', '.form_EditExit .btn_EditExit', function() {
			var RezId 	  = document.getElementById('Id_doc').value;
			var TimeStart = document.getElementById('timeStart').value;
			var DateEnd   = document.getElementById('dateEnd').value;
			var TimeEnd   = document.getElementById('timeEnd').value;
	        var Sc = Window.Sc;
			var data = {
				RezId:RezId, //id документа
				TimeStart:TimeStart,//время начала
				DateEnd: DateEnd, //дата выхода
				TimeEnd:TimeEnd, //время выхода
				Sc:Sc
				};
			$.post('time_list_old.php',data)
				.done(function(data) {
				if(data==1){ //если пришло 1, значит запрос к базе прошел
					//отображаем ссылку для распечатки корешка
					console.log(data)
					$("<span class='alert alert-info' id='NewSpan'>Изменена сверхурочка</span>").insertBefore('#editExit');
					$('#editExit').hide();	
					$('#editDel').hide();	
					$(".Action_"+RezId+"").html("<span class='id' style='visibility: hidden'>"+RezId+"</span><a href='#myModal3' role='button' class='btn' data-toggle='modal'><img src='../img/edit.png' width='30' height='30' alt='' />	</a>");	
					if (Sc==0) {
						$(".TimeOut_"+RezId+"").html(TimeStart);
						$(".DateExit_"+RezId+"").html(DateEnd);
						$(".TimeExit_"+RezId+"").html(TimeEnd);
					}
					else 
					{ 
						$(".TimeOut_"+RezId+"").html(TimeStart);
						$(".DateOut_"+RezId+"").html(DateEnd);
						$(".TimeExit_"+RezId+"").html(TimeEnd);
					}
				}
				else {
					alert('Ошибка в SQL-запросе. Пожалуйста, попробуйте еще раз'+data);		
				}			 
			}).fail(function(data) {
			  alert('Ошибка при отправке данных на форму. Пожалуйста, попробуйте еще раз');
			});			
			return false;
		  });
})

 </script>