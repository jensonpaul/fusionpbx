<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes fileshp";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//authorized referrer
	if (stristr($_SERVER["HTTP_REFERER"], '/calls_active.php') === false) {
		echo "access denied";
		exit;
	}

//authorized commands
	if (!empty($_REQUEST['customer_access_code']) && !empty($_REQUEST['call_uuid'])) {

		//validate the token
			/* $token = new token;
			if (!$token->validate('/app/calls_active/calls_active_inc.php')) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: calls_active.php');
				exit;
			} */

		//verify submitted call uuid
			if (is_uuid($_REQUEST['call_uuid'])) {
				$calls[$_REQUEST['call_uuid']] = $_REQUEST['customer_access_code'];
			}

		//iterate through calls\store customer access code in db
			if (is_array($calls) && @sizeof($calls) != 0) {

				$database = new database;
				$database->connect();

				$myClassReflection = new ReflectionClass(get_class($database));

				$db = $myClassReflection->getProperty('driver');
				$db->setAccessible(true);
				$driver = $db->getValue($database);

				$db = $myClassReflection->getProperty('host');
				$db->setAccessible(true);
				$host = $db->getValue($database);

				$db = $myClassReflection->getProperty('port');
				$db->setAccessible(true);
				$port = $db->getValue($database);

				$db = $myClassReflection->getProperty('db_name');
				$db->setAccessible(true);
				$dbname = $db->getValue($database);

				$db = $myClassReflection->getProperty('username');
				$db->setAccessible(true);
				$username = $db->getValue($database);

				$db = $myClassReflection->getProperty('password');
				$db->setAccessible(true);
				$password = $db->getValue($database);

				$dsn = "$driver:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";

				try{
					// create a PostgreSQL database connection
					$conn = new PDO($dsn);

					// display a message if connected to the PostgreSQL successfully
					if($conn){
						$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
						$conn->exec("set names utf8");
						echo "Connected to the <strong>$db</strong> database successfully!";
					}
				} catch (PDOException $e){
					// report error message
					echo $e->getMessage();
				}

				try {
					$data = [
						'customer_access_code' => $_POST['customer_access_code'],
						'xml_cdr_uuid' => $_POST['call_uuid'],
					];

					$sql = 'INSERT INTO v_xml_cdr_customer_access_code
							(customer_access_code, xml_cdr_uuid) VALUES (:customer_access_code, :xml_cdr_uuid)';

					$stmt = $conn->prepare($sql);
					$stmt->execute($data);
				} catch (PDOException $e) {
					echo $e->getMessage();
				}

				//set message
					message::add('Access code saved','positive');

			}

		//redirect
			header('Location: calls_active.php');
			exit;

	}
	else {
		echo "access denied";
		exit;
	}

?>
