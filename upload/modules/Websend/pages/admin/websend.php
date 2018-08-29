<?php
/*
 *	Made by Samerton
 *  https://github.com/samerton
 *  NamelessMC version 2.0.0-pr4
 *
 *  License: MIT
 *
 *  Websend configuration page
 */

// Can the user view the AdminCP?
if($user->isLoggedIn()){
	if(!$user->canViewACP()){
		// No
		Redirect::to(URL::build('/'));
		die();
	} else {
		// Check the user has re-authenticated
		if(!$user->isAdmLoggedIn()){
			// They haven't, do so now
			Redirect::to(URL::build('/admin/auth'));
			die();
		} else {
			if(!$user->hasPermission('admincp.websend')){
				Redirect::to(URL::build('/admin'));
				die();
			}
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
	die();
}

$page = 'admin';
$admin_page = 'websend';
?>
<!DOCTYPE html>
<html lang="<?php echo(defined('HTML_LANG') ? HTML_LANG : 'en'); ?>" <?php if(defined('HTML_RTL') && HTML_RTL === true) echo ' dir="rtl"'; ?>>
<head>
	<!-- Standard Meta -->
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

	<?php
	$title = $language->get('admin', 'admin_cp');
	require(ROOT_PATH . '/core/templates/admin_header.php');
	?>

	<link rel="stylesheet" href="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/switchery/switchery.min.css">

</head>
<body>
<?php require(ROOT_PATH . '/modules/Core/pages/admin/navbar.php'); ?>
<div class="container">
	<div class="row">
		<div class="col-md-3">
			<?php require(ROOT_PATH . '/modules/Core/pages/admin/sidebar.php'); ?>
		</div>
		<div class="col-md-9">
			<div class="card">
				<div class="card-block">
					<h3 style="display:inline;"><?php echo $websend_language->get('language', 'websend'); ?></h3>
					<?php if(isset($_GET['hook'])){ ?>
						<a class="btn btn-primary float-right" href="<?php echo URL::build('/admin/websend'); ?>"><?php echo $language->get('general', 'back'); ?></a>
					<?php } ?>
					<hr />
					<?php if(isset($ws_conf_err)) echo '<div class="alert alert-danger">' . $websend_language->get('language', 'unable_to_create_config') . '</div>'; ?>
					<?php
					if(!isset($_GET['hook'])){

						if(Input::exists()){
							if(Token::check(Input::get('token'))){
								$address = (isset($_POST['address']) ? $_POST['address'] : '');
								$port = (isset($_POST['port']) ? (int)$_POST['port'] : 4445);
								$password = (isset($_POST['password']) ? $_POST['password'] : $ws_config['websend_password']);

								$ws_conf =  '<?php' . PHP_EOL .
											'$ws_config = array(' . PHP_EOL .
											'   \'websend_server_address\' => \'' . $address . '\',' . PHP_EOL .
											'   \'websend_server_port\' => ' . $port . ',' . PHP_EOL .
											'   \'websend_password\' => \'' . $password . '\'' . PHP_EOL .
											');';

								if(file_put_contents(ROOT_PATH . '/modules/Websend/config.php', $ws_conf) === false){
									$error = $websend_language->get('language', 'unable_to_create_config');

								} else {
									Redirect::to(URL::build('/admin/websend'));
									die();
								}


							} else
								$error = $language->get('general', 'invalid_token');
						}

						if(isset($error))
							echo '<div class="alert alert-danger">' . $error . '</div>';

						?>
					<strong><?php echo $websend_language->get('language', 'available_hooks'); ?></strong><br />
					<?php
					$hookQuery = $queries->getWhere('websend_commands', array('enabled', '=', 1));

					$hooks = array();
					foreach($hookQuery as $hook){
						$hooks[] = $hook->hook;
					}

					$allHooks = HookHandler::getHooks();
					foreach($allHooks as $hook => $description){
						echo '<a href="' . URL::build('/admin/websend/', 'hook=' . Output::getClean($hook)) . '">' . $description . '</a>';

						if(in_array($hook, $hooks)){
							echo ' <span class="badge badge-success">' . $websend_language->get('language', 'enabled') . '</span>';
						} else {
							echo ' <span class="badge badge-danger">' . $websend_language->get('language', 'disabled') . '</span>';
						}

						echo '<br />';
					}
					?>

					<hr />

					<strong><?php echo $websend_language->get('language', 'connection_details'); ?></strong>

					<form action="" method="post">
						<div class="form-group">
							<label for="inputAddress"><?php echo $websend_language->get('language', 'connection_address'); ?></label>
							<input type="text" class="form-control" name="address" id="inputAddress" value="<?php echo Output::getClean($ws_config['websend_server_address']); ?>">
						</div>
						<div class="form-group">
							<label for="inputPort"><?php echo $websend_language->get('language', 'connection_port'); ?></label>
							<input type="text" class="form-control" name="port id="inputPort" value="<?php echo Output::getClean($ws_config['websend_server_port']); ?>">
						</div>
						<div class="form-group">
							<label for="inputPassword"><?php echo $websend_language->get('language', 'connection_password'); ?></label>
							<input type="password" class="form-control" name="password" id="inputPassword">
						</div>
						<div class="form-group">
							<input type="hidden" name="token" value="<?php echo Token::get(); ?>">
							<input type="submit" class="btn btn-primary" value="<?php echo $language->get('general', 'submit'); ?>">
						</div>
					</form>

					<?php
					} else {
						$hook = HookHandler::getHook($_GET['hook']);

						if(!$hook){
							Redirect::to(URL::build('/admin/websend'));
							die();
						}

						$db_hook = $queries->getWhere('websend_commands', array('hook', '=', Output::getClean($_GET['hook'])));
						if(count($db_hook))
							$db_hook = $db_hook[0];
						else
							$db_hook = null;

						if(Input::exists()){
							if(Token::check(Input::get('token'))){
								if(isset($_POST['enable_hook']) && $_POST['enable_hook'] == 'on')
									$enabled = 1;
								else
									$enabled = 0;

								if(isset($_POST['commands']))
									$commands = $_POST['commands'];
								else
									$commands = '';

								if(is_null($db_hook)){
									$queries->create('websend_commands', array(
										'hook' => $_GET['hook'],
										'commands' => $commands,
										'enabled' => $enabled
									));
								} else {
									$queries->update('websend_commands', $db_hook->id, array(
										'commands' => $commands,
										'enabled' => $enabled
									));
								}

								$db_hook = $queries->getWhere('websend_commands', array('hook', '=', Output::getClean($_GET['hook'])));
								$db_hook = $db_hook[0];

							} else
								$error = $language->get('general', 'invalid_token');
						}
						?>
						<strong><?php echo Output::getClean($hook['description']); ?></strong><br />

						<?php if(isset($error)) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>

						<form action="" method="post">
							<div class="form-group">
								<label for="inputEnable"><?php echo $websend_language->get('language', 'enable_hook'); ?></label>
								<input type="checkbox" name="enable_hook" id="inputEnable" class="js-switch" <?php if(!is_null($db_hook) && $db_hook->enabled == 1) echo 'checked '; ?>/>
							</div>
							<div class="form-group">
								<div class="alert alert-info">
									<?php
									echo $websend_language->get('language', 'commands_information');
									echo '<ul>';
									if(count($hook['params'])){
										foreach($hook['params'] as $param => $desc){
											echo '<li><strong>{' . Output::getClean($param) . '}</strong> - ' . Output::getClean($desc) . '</li>';
										}
									}
									echo '</ul>';
									?>
								</div>
								<label for="inputCommands"><?php echo $websend_language->get('language', 'commands'); ?></label>
								<textarea id="inputCommands" name="commands" class="form-control"><?php if(!is_null($db_hook)) echo Output::getClean($db_hook->commands); ?></textarea>
							</div>
							<div class="form-group">
								<input type="hidden" name="token" value="<?php echo Token::get(); ?>">
								<input type="submit" class="btn btn-primary" value="<?php echo $language->get('general', 'submit'); ?>">
							</div>
						</form>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php require(ROOT_PATH . '/modules/Core/pages/admin/footer.php'); ?>
<?php require(ROOT_PATH . '/modules/Core/pages/admin/scripts.php'); ?>

<script src="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/switchery/switchery.min.js"></script>

<script type="text/javascript">
	var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
	elems.forEach(function(html) {
		var switchery = new Switchery(html);
	});
</script>

</body>
</html>