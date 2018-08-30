<?php

//запуск через консоль
if(PHP_SAPI == 'cli')
{
	//если количество параметров меньше двух 
	if($argym<2)
		// выводим сообщение
		die("Введите параметры.");
	//перебираем все параметры запуска
	for ($i=1; $i<$argym; $i++) 
	{
		//поиск символа "=" в параметре, возвращаемое значение - строка
		$argument=strstr($argum1[$i], "=", TRUE);
		//если символ найден
		if($argument)
		{
			//убираем символ "=" из строки
			$argument=str_replace("=", "", $argument);
			//если это текст "date_from"
			if($argument=="date_from")
			{
				//берем часть параметра справа от символа "=" вместе с ним
				$argument=strstr($argym1[$i], "=", FALSE);
				//убираем символ "=" 
				$date_from=str_replace("=", "", $argument);
			}
			//если это текст "date_to"
			if($argument=="date_to")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$date_to=str_replace("=", "", $argument);
			}
			//если это текст "from"
			if($argument=="from")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$from=str_replace("=", "", $argument);
			}
			//если  это текст "to"
			if($argument=="to")
			{
				$argument=strstr($argym1[$i], "=", FALSE);
				$to=str_replace("=", "", $argument);
			}
		}
	}
}
//запуск через http
else
{
	//если отсутствуют необходимые параметры
	if(($_GET["to"] === null)&&($_GET["from"] === null)&&($_GET["date_to"] === null)&&($_GET["date_from"] === null)) 
	{
		//выводим сообщение
		die("Введите параметры фильтрации.");
	}
	//осуществляем get-запрос 
	$date_from=$_GET['date_from'];
	$date_to=$_GET['date_to'];
	$from=$_GET['from'];
	$to=$_GET['to'];
}
//подключение к почтовому ящику, аргумент функции - название папки в почтовом ящике
function connecting($mail_f)
{
	//подключаемся к почтовому ящику
	$mailbox = @imap_open("{imap.gmail.com:993/imap/ssl}".imap_utf8_to_mutf7($mail_f), "login", "password");
	//если  удалось
	if($mailbox)
	{
		//возвращаемое значение - поток почтового ящика
		return $mailbox;
	}
	//если не удалось
	else
	{
		// выводим сообщение
		die("Ошибка подключения к почтовому ящику.");
	}
}

//информации о письме 
//$mailbox - поток к почтовому ящику
//$num - номер письма в ящике
// $finish - поток к выходному файлу 
function information($mailbox, $num, $finish)
{
	//считываем заголовок письма
	$letter= imap_header($mailbox, $num);
	//декодируем тему письма
	$subject=imap_mime_header_decode($letter->subject);
	//записываем в одну строку
	for ($i=0; $i<count($subject); $i++)
	{
		$subj.=$subject[$i]->text;
	}

	//вытаскиваем отправителя
	$elements = $letter->from;
	//выводим почту отправителя
	foreach ($elements as $element) 
	{
		$sender=($element->mailbox)."@".($element->host);
	}

	//вытаскиваем получателя
	$elements = $letter->to;
	//выводим почту получателя
	foreach ($elements as $element) 
	{
		$receiver=($element->mailbox)."@".($element->host);
	}

	// дата в формате udate
	$date = $letter->udate;
	//выводим дату
	$date=date("j.m.Y H:i:s",$date);

	//проверяем X-EVENT
	//считываем полный заголовок письма
	$str=imap_fetchheader($mailbox, $num);
	//ищем в заголовке строку "X-EVENT"
	$position=strpos($str, "X-EVENT");
	//если есть
	if($position)
	{
		//считываем текст после "X-EVENT_NAME: " 		$x_event=substr($str, $position+strlen("X-EVENT_NAME: "));
	}

	//записываем строку (дата получения, email отправителя, email получателя, тема письма, X-EVENT_NAME) в выходной файл
	fputcsv($finish, array($date,iconv("utf-8" ,"windows-1251", $sender), iconv("utf-8" ,"windows-1251", $receiver), iconv("utf-8" ,"windows-1251", $subj), iconv("utf-8" ,"windows-1251", $x_event)),";");
}
// строка для хранения параметров поиска
$criteria='';
//если параметр "to" есть
if($to!=null)
{
	//добавляем его значение в строку
	$criteria.=' TO '.$to;
}
//если параметр "from" есть
if($from!=null)
{
	$criteria.=' FROM '.$from;
}
//если параметр "date_from" есть
if($date_from!=null)
{
$criteria.=' SINCE "'.date("j F Y",strtotime($date_from)).'"';
}
//если параметр "date_to" есть
if($date_to!=null)
{
	$criteria.=' BEFORE "'.date("j F Y",strtotime($date_to)).'"';
}

// имя выходного файла-дата
$outputfile=date('j.m.Y H-i-s', time()).".csv";
//открываем файл для записи
$finish = fopen($outputfile, 'w');
//если открыть файл не удалось
if(!$finish)
{
	//прекращаем выполнение скрипта и выводим сообщение
	die("Ошибка. ");
}
//записываем шапку файла
fputcsv($finish, array(iconv("utf-8" ,"windows-1251", "Дата получения"),iconv("utf-8" ,"windows-1251", "Email отправителя"), iconv("utf-8" ,"windows-1251", "Email получателя"), iconv("utf-8" ,"windows-1251", "Тема письма"), iconv("utf-8" ,"windows-1251", "X-EVENT_NAME")),";");
//закрываем выходной файл
fclose($finish);

//подключение к почтовому ящику и открытие папки "Отправленные"
$mailbox=connecting('Отправленные');
//поиск писем в папке
$array = imap_search ( $mailbox , $criteria );
//если нашлось 
if(!empty($array))
{
	//выводим сообщение о результатах поиска
	echo "По заданным параметрам найдено  писем: ".count($array).". ";
	//открываем файл для записи
	$finish = @fopen($outputfile, 'a');
	//если открыть файл не удалось
	if(!$finish)
	{
		//прекращаем выполнение скрипта и выводим сообщение
		die("Ошибка. ");
	}
	//перебираем найденные письма
	foreach($array as $num)
	{
		//вызываем функцию для обработки письма
		information($mailbox, $num, $finish);
	}
	//закрываем выходной файл
	fclose($finish);
}
//если нет писем
else 
{
	//выводим сообщение
	echo "Писем не найдено. ";
}
//закрываем поток к почтовому ящику
imap_close($mailbox);

//подключение к почтовому ящику в папке ("Входящие")
$mailbox=connecting('');
//поиск писем в папке по заданным критериям поиска
$array = imap_search ( $mailbox , $criteria );
//если нашлось хотя бы одно письмо
if(!empty($array))
{
	//выводим сообщение о результатах поиска
	echo "Найдено писем: ".count($array).". ";
	//открываем файл для записи
	$fininsh = @fopen($outputfile, 'a');
	//если открыть файл не удалось
	if(!$finish)
	{
		//прекращаем выполнение скрипта и выводим сообщение
		die("Ошибка. ");
	}
	//перебираем найденные письма
	foreach($array as $num)
	{
		//вызываем функцию для обработки письма
	information($mailbox, $num, $finish);
	}
	//закрываем выходной файл
	fclose($finish);
}
//если писем не найдено
else 
{
	//выводим сообщение
	echo " Писем не найдено. ";
}
//закрываем поток к почтовому ящику
imap_close($mailbox);
?>