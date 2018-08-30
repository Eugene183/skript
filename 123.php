<?php

//������ ����� �������
if(PHP_SAPI == 'cli')
{
	//���� ���������� ���������� ������ ���� 
	if($argym<2)
		// ������� ���������
		die("������� ���������.");
	//���������� ��� ��������� �������
	for ($i=1; $i<$argym; $i++) 
	{
		//����� ������� "=" � ���������, ������������ �������� - ������
		$argument=strstr($argum1[$i], "=", TRUE);
		//���� ������ ������
		if($argument)
		{
			//������� ������ "=" �� ������
			$argument=str_replace("=", "", $argument);
			//���� ��� ����� "date_from"
			if($argument=="date_from")
			{
				//����� ����� ��������� ������ �� ������� "=" ������ � ���
				$argument=strstr($argym1[$i], "=", FALSE);
				//������� ������ "=" 
				$date_from=str_replace("=", "", $argument);
			}
			//���� ��� ����� "date_to"
			if($argument=="date_to")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$date_to=str_replace("=", "", $argument);
			}
			//���� ��� ����� "from"
			if($argument=="from")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$from=str_replace("=", "", $argument);
			}
			//����  ��� ����� "to"
			if($argument=="to")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$to=str_replace("=", "", $argument);
			}
		}
	}
}
//������ ����� http
else
{
	//���� ����������� ����������� ���������
	if(($_GET["to"] === null)&&($_GET["from"] === null)&&($_GET["date_to"] === null)&&($_GET["date_from"] === null)) 
	{
		//������� ���������
		die("������� ��������� ����������.");
	}
	//������������ get-������ 
	$date_from=$_GET['date_from'];
	$date_to=$_GET['date_to'];
	$from=$_GET['from'];
	$to=$_GET['to'];
}
//����������� � ��������� �����, �������� ������� - �������� ����� � �������� �����
function connecting($mail_f)
{
	//������������ � ��������� �����
	$mailbox = @imap_open("{imap.gmail.com:993/imap/ssl}".imap_utf8_to_mutf7($mail_f), "login", "password");
	//����  �������
	if($mailbox)
	{
		//������������ �������� - ����� ��������� �����
		return $mailbox;
	}
	//���� �� �������
	else
	{
		// ������� ���������
		die("������ ����������� � ��������� �����.");
	}
}

//���������� � ������ 
//$mailbox - ����� � ��������� �����
//$num - ����� ������ � �����
// $finish - ����� � ��������� ����� 
function information($mailbox, $num, $finish)
{
	//��������� ��������� ������
	$letter= imap_header($mailbox, $num);
	//���������� ���� ������
	$subject=imap_mime_header_decode($letter->subject);
	//���������� � ���� ������
	for ($i=0; $i<count($subject); $i++)
	{
		$subj.=$subject[$i]->text;
	}

	//����������� �����������
	$elements = $letter->from;
	//������� ����� �����������
	foreach ($elements as $element) 
	{
		$sender=($element->mailbox)."@".($element->host);
	}

	//����������� ����������
	$elements = $letter->to;
	//������� ����� ����������
	foreach ($elements as $element) 
	{
		$receiver=($element->mailbox)."@".($element->host);
	}

	// ���� � ������� udate
	$date = $letter->udate;
	//������� ����
	$date=date("j.m.Y H:i:s",$date);

	//��������� X-EVENT
	//��������� ������ ��������� ������
	$str=imap_fetchheader($mailbox, $num);
	//���� � ��������� ������ "X-EVENT"
	$position=strpos($str, "X-EVENT");
	//���� ����
	if($position)
	{
		//��������� ����� ����� "X-EVENT_NAME: " 		$x_event=substr($str, $position+strlen("X-EVENT_NAME: "));
	}

	//���������� ������ (���� ���������, email �����������, email ����������, ���� ������, X-EVENT_NAME) � �������� ����
	fputcsv($finish, array($date,iconv("utf-8" ,"windows-1251", $sender), iconv("utf-8" ,"windows-1251", $receiver), iconv("utf-8" ,"windows-1251", $subj), iconv("utf-8" ,"windows-1251", $x_event)),";");
}
// ������ ��� �������� ���������� ������
$criteria='';
//���� �������� "to" ����
if($to!=null)
{
	//��������� ��� �������� � ������
	$criteria.=' TO '.$to;
}
//���� �������� "from" ����
if($from!=null)
{
	$criteria.=' FROM '.$from;
}
//���� �������� "date_from" ����
if($date_from!=null)
{
$criteria.=' SINCE "'.date("j F Y",strtotime($date_from)).'"';
}
//���� �������� "date_to" ����
if($date_to!=null)
{
	$criteria.=' BEFORE "'.date("j F Y",strtotime($date_to)).'"';
}

// ��� ��������� �����-����
$outputfile=date('j.m.Y H-i-s', time()).".csv";
//��������� ���� ��� ������
$finish = fopen($outputfile, 'w');
//���� ������� ���� �� �������
if(!$finish)
{
	//���������� ���������� ������� � ������� ���������
	die("������. ");
}
//���������� ����� �����
fputcsv($finish, array(iconv("utf-8" ,"windows-1251", "���� ���������"),iconv("utf-8" ,"windows-1251", "Email �����������"), iconv("utf-8" ,"windows-1251", "Email ����������"), iconv("utf-8" ,"windows-1251", "���� ������"), iconv("utf-8" ,"windows-1251", "X-EVENT_NAME")),";");
//��������� �������� ����
fclose($finish);

//����������� � ��������� ����� � �������� ����� "������������"
$mailbox=connecting('������������');
//����� ����� � �����
$array = imap_search ( $mailbox , $criteria );
//���� ������� 
if(!empty($array))
{
	//������� ��������� � ����������� ������
	echo "�� �������� ���������� �������  �����: ".count($array).". ";
	//��������� ���� ��� ������
	$finish = @fopen($outputfile, 'a');
	//���� ������� ���� �� �������
	if(!$finish)
	{
		//���������� ���������� ������� � ������� ���������
		die("������. ");
	}
	//���������� ��������� ������
	foreach($array as $num)
	{
		//�������� ������� ��� ��������� ������
		information($mailbox, $num, $finish);
	}
	//��������� �������� ����
	fclose($finish);
}
//���� ��� �����
else 
{
	//������� ���������
	echo "����� �� �������. ";
}
//��������� ����� � ��������� �����
imap_close($mailbox);

//����������� � ��������� ����� � ����� ("��������")
$mailbox=connecting('');
//����� ����� � ����� �� �������� ��������� ������
$array = imap_search ( $mailbox , $criteria );
//���� ������� ���� �� ���� ������
if(!empty($array))
{
	//������� ��������� � ����������� ������
	echo "������� �����: ".count($array).". ";
	//��������� ���� ��� ������
	$fininsh = @fopen($outputfile, 'a');
	//���� ������� ���� �� �������
	if(!$finish)
	{
		//���������� ���������� ������� � ������� ���������
		die("������. ");
	}
	//���������� ��������� ������
	foreach($array as $num)
	{
		//�������� ������� ��� ��������� ������
	information($mailbox, $num, $finish);
	}
	//��������� �������� ����
	fclose($finish);
}
//���� ����� �� �������
else 
{
	//������� ���������
	echo " ����� �� �������. ";
}
//��������� ����� � ��������� �����
imap_close($mailbox);
?>