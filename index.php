<?php
//ANSI编码
//By xzp44@163.com 2016.1.8
//需要把php_oracle.dll拷贝到system32下
error_reporting(E_ERROR);
date_default_timezone_set("Asia/chongqing");	//设置时区
require_once('SimplifiedQuanPin.php');

session_start();

$randstr = md5(time().mt_rand(10,1000));
$token = isset($_GET['token']) ? $_GET['token'] : null;
isset($_SESSION['token']) ? null : ($_SESSION['token']=1);
$msg = '该插件可批量向华海PACS导入数据，谨慎使用！';

if(isset($_POST['submit'])&&$_POST['submit']==='SAVE'&&$_SESSION['token']!=$token){
	$data = explode("\n", $_POST['data']);
	//print_r($data);
	foreach($data as $k=>$v){
		$v = trim($v);
		if($v){
			$line = explode("	", $v);
			//print_r($line);

			$now = time();
			$date = date('Y-m-d',$now);
			$time = date('H:i:s',$now);
			$date1 = date('YmdHis',$now);

			$room = str_replace(array('01', '02'), array('DSA1', 'DSA'), $line[0]);
			$birth = (date('Y')-$line[8]).'-'.date('m-d');
			
			$sqp = new SimplifiedQuanPin;
			$enname = $sqp->getFullSpell($line[3]);
			
			preg_match('/心|冠|射|搏|房|室|动脉导管|瓦耳萨瓦氏窦/',$line[11],$matches);
			$part = $matches?'心血管':'外周';
			
			$operation = trim(substr($line[11],0,50));//手术名称限制字符50
			
			$sql4 = "SELECT MAXPATIENTID.nextval from dual";
			$sql5 = "SELECT MAXSTUDYID.nextval from dual";
			$sql6 = "SELECT MAXPHOTONO.nextval from dual";
			$sql7 = "SELECT MAXSERIESID.nextval from dual";

			$patientid = oracle($sql4);
			$patientid = $patientid[0]["NEXTVAL"];
			$studyid = oracle($sql5);
			$studyid = $studyid[0]["NEXTVAL"];
			$photono = oracle($sql6);
			$photono = $photono[0]["NEXTVAL"];
			$seriesid = oracle($sql7);
			$seriesid = $seriesid[0]["NEXTVAL"];
			
			$sql1 = "INSERT INTO r_studies t (	STUDYID, 		PATIENTID, 		AGE,			LODGEHOSPITAL, 		LODGESECTION,	LODGEDOCTOR,	LODGEDATE, 							BEDNO, 			CLIISINPAT,	ENROLDOCTOR, 	ENROLTIME, 	EXIGENCE,	STATUS,		CLASSNAME,	PHOTONO,		PARTOFCHECK,	STUINSUID, 											INHOSPITALNO,	APPLYNUMBER, 			ACCESSIONNUMBER,	T_PRIORITY) 
									VALUES (		'{$studyid}',	'{$patientid}',	'{$line[8]} 岁',	'汉****医院', 	'{$line[5]}', 	'', 			to_date('{$date}','yyyy-mm-dd'), 	'{$line[6]}',	'住院',		'**平', 	'{$time}',	'0',		'已登记',	'{$room}',	'{$photono}',	'{$part}',			'1.2.840.31314.14143234.{$date1}.{$studyid}',	'{$line[4]}',	'{$studyid}',			'{$studyid}',		'普通')";
			$sql2 = "INSERT INTO r_patient t (	PATIENTID,	HISID, 		PHOTONO, 		NAME,			ENGNAME,		SEX,			BIRTHDATE,							TELEPHONE,	MODIFIED) 
									VALUES ( 	'{$patientid}',	'0',	'{$photono}',	'{$line[3]}',	'{$enname}',	'{$line[7]}',	to_date('{$birth}','yyyy-mm-dd'), 	'0', 		'0')";
			$sql3 = "INSERT INTO r_series t (SERIESID, 		STUDYID, 		DIRECTION ) 
									VALUES ( '{$seriesid}', 	'{$studyid}', 	'{$operation}')";
			
			if(false===oracle($sql1,'w')){
				$msg = '[1/3][STUDIES]写入失败！';
			}else{
				if(false===oracle($sql2, 'w')){
					$msg = '[2/3][PATIENT]写入失败！';
				}else{
					if(false===oracle($sql3, 'w')){
						$msg = '[3/3][SERIES]写入失败！';
					}else{
						$_SESSION['token'] = $token;
						$msg = '导入成功！';
					}
				}
			}
		}
	
	}
}elseif($_SESSION['token']== $token){
	$msg = '请勿重复提交表单！';
}


function oracle($sql, $method='r'){
	if(!$sql) return false;
	
	$db = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.2.80)(PORT = 1521)))(CONNECT_DATA=(SID=oracle)))"; 
	$conn = oci_connect("user","pwd",$db,"US7ASCII");
	
	$stmt = oci_parse($conn, $sql);
	$state = oci_execute($stmt);
	
	$data = null;
	if($method=='r'){
		while($result = oci_fetch_array($stmt, OCI_ASSOC)){
			$data[] = $result; //无结果返回null
		}
	}elseif($method=='w'){
		$data = $state;
	}
	oci_free_statement($stmt);
	oci_close($conn);
	//var_dump($data);
	return $data;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=US7ASCII" />
<link href="/OMS/theme/images/favicon.png" rel="shortcut icon" />
<script src="jquery.min.js" type="text/javascript"></script>
<title>Hack for HHRIS @xiangdatong</title>
<style>
body{font-size:12px;background-color:#000;color:green;}
input[type="text"]{background:0;border:0;outline:0;border-bottom:1px solid green;color:green;padding:0 5px;width:80px;}
input[type="submit"]{background:0;border:1px solid green;outline:0;color:green;cursor:pointer;}
p{margin:0 0 10px 0;}
textarea {width:100%;border:1px solid green;background-color:black;color:green;outline:none;}
</style>
</head>
<body>
	<div style="margin:15px 0;">
		<h1>数据批量录入插件 for HHRIS</h1>
	</div>
	
	<div class="" style="margin:15px 0;">
		<div style="padding:0 12px 10px;">
			<p>INPUT DATA： <span style="color:red;"><?php echo $msg ?></span></p>
			<form action="?token=<?php echo $randstr ?>" method="post">
			<textarea name="data" cols="" rows="25" wrap="off"></textarea>
			<p style="padding:20px 0;"><input id="SAVE" name="submit" type="submit" value="SAVE" /></p>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript">
$(function () { 
	$("form").submit(function(){
		var text = $("textarea").val();
		text = text.replace(/( )*\t/g, "\t");
		if(text.length<32){
			$("span").text("非法数据！"); 
			return false;
		}
		
		var res = text.match(/\d{2}[\t \d:\w]{10,}[\u2E80-\u9FFF]{2,5}\t\d{7,}\t[\s\S].+[男女]\t\d{1,3}[\s\S].+[\d-: ]{19}\t.*/g);
		if(!res){
			$("span").text("非法数据！"); 
			return false;
		}
		$("span").text("解析成功！");
		$("textarea").val(text);
		var r=confirm("成功解析出 "+res.length+" 条数据，确定要导入这些数据吗？");
		if (r==true){
			return true;
		}else{
			return false;
		}
	});
});
</script> 
</body>
</html>
