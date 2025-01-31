<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$customPath = config::byKey('userImgPath', 'JeedomConnect');
sendVarToJS('userImgPath', $customPath);

// Déclaration des variables obligatoires
$plugin = plugin::byId('JeedomConnect');
sendVarToJS('eqType', $plugin->getId());

$isExpert = config::byKey('isExpert', 'JeedomConnect') ? true : false;
sendVarToJS('isJcExpert', $isExpert);

$userHash = '';
if (isConnect()) {
	if (isset($_SESSION['user']) && is_object($_SESSION['user'])) {
		$user = user::byId($_SESSION['user']->getId());
		if (is_object($user)) {
			// JCLog::debug('user session:' . $user->getHash());
			$userHash = $user->getHash();
		}
	}
}
sendVarToJS('userHash', $userHash);

/** @var array<JeedomConnect> $eqLogics */
$eqLogics = eqLogic::byType($plugin->getId());

list($widgetInError, $roomInError) = JeedomConnectWidget::checkCmdSetupInWidgets();

foreach ($roomInError as $widgetId) {
	JCLog::debug("removing room for widget Id " . $widgetId);
	//remove key room for widget with unexisting room
	JeedomConnectWidget::updateConfig($widgetId, 'room');
}

$widgetArray = JeedomConnectWidget::getWidgets();

$jcFilter = $_GET['jcFilter'] ?? '';
$orderBy = $_GET['jcOrderBy'] ?? config::byKey('jcOrderByDefault', 'JeedomConnect', 'object');
$widgetSearch = $_GET['jcSearch'] ?? '';

sendVarToJS('jcOrderBy', $orderBy);

switch ($orderBy) {
	case 'name':
		$widgetName = array_column($widgetArray, 'name');
		array_multisort($widgetName, SORT_ASC, $widgetArray);
		break;

	case 'type':
		$widgetType = array_column($widgetArray, 'type');
		$widgetName = array_column($widgetArray, 'name');
		array_multisort($widgetType, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
		break;

	default:
		// $roomName  = array_column($widgetArray, 'roomName');
		// $widgetName = array_column($widgetArray, 'name');

		// array_multisort($roomName, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
		break;
}

$allConfig = JeedomConnect::getWidgetParam();
$widgetTypeArray = array();

$listWidget = '';

$hasErrorPage = false;
foreach ($widgetArray as $widget) {
	$needSign = '';
	$hasError = '';
	$img = $widget['img'];

	$opacity = $widget['enable'] ? '' : 'disableCard';
	$widgetName = $widget['name'];
	$widgetRoom = $widget['roomName'];;
	$id = $widget['id'];
	$widgetType = $widget['type'];

	$styleHide = ($jcFilter == '') ? '' : ($jcFilter == $widgetType ? '' : 'style="display:none;"');

	if (in_array($id, $widgetInError)) {
		$hasError = 'hasError';
		$needSign = '<i class="fas fa-exclamation-circle" style="color: var(--al-danger-color) !important;" title="Commandes orphelines"></i>';
		$hasErrorPage =  true;
	}

	//used later by the filter select item
	if (!in_array($widgetType, $widgetTypeArray, true)) $widgetTypeArray[$widgetType] = $allConfig[$widgetType];

	$name = '<span class="label labelObjectHuman" style="text-shadow : none;">' . $widgetRoom . '</span><br><strong> ' . $widgetName . ' ' .  $needSign . '</strong>';

	$listWidget .= '<div class="widgetDisplayCard cursor  ' . $hasError . ' ' . $opacity . '" ' . $styleHide . ' title="id=' . $id . '" data-widget_id="' . $id . '" data-widget_type="' . $widgetType . '">';
	$listWidget .= '<img src="' . $img . '"/>';
	$listWidget .= '<br>';
	$listWidget .= '<span class="name">' . $name . '</span>';
	$listWidget .= '</div>';
}


// $optionsOrderBy = $_GET['jcOrderBy'] ?? '';
$optionsOrderBy = '';
$orderByArray = array(
	"object" => "Pièce",
	"name" => "Nom",
	"type" => "Type"
);

foreach ($orderByArray as $key => $value) {
	$selected = ($key ==  $orderBy) ? 'selected' : '';
	$optionsOrderBy .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}


asort($widgetTypeArray);
$typeSelection2 = '';
$hasSelected = false;
foreach ($widgetTypeArray as $key => $value) {
	$selected = ($key ==  $jcFilter) ? 'selected' : '';
	$hasSelected = $hasSelected || ($key ==  $jcFilter);
	$typeSelection2 .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}
$sel = $hasSelected ? '' : 'selected';
$typeSelection = '<option value="none" ' . $sel . '>Tous</option>' . $typeSelection2;


$infoPlugin = JeedomConnectUtils::getInstallDetails();

$displayWarning = config::byKey('displayWarning', 'JeedomConnect', 0) < 3;

?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">

		<div class="row">
			<div class="col-sm-10">
				<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
				<!-- Boutons de gestion du plugin -->
				<div class="eqLogicThumbnailContainer">
					<div class="cursor eqLogicAction " data-action="add" style="color:rgb(27,161,242);">
						<i class="fas fa-mobile-alt"></i>
						<br>
						<span>{{Ajouter un Appareil}}</span>
					</div>
					<div class="cursor eqLogicAction " data-action="addWidget" style="color:rgb(27,161,242);">
						<i class="fas fa-icons"></i>
						<br>
						<span style="color:var(--txt-color)">{{Ajouter un Widget}}</span>
					</div>
					<div class="cursor eqLogicAction " data-action="addWidgetBulk" style="color:rgb(27,161,242);">
						<i class="fas fa-magic"></i>
						<br>
						<span style="color:var(--txt-color)">{{Création de widgets en masse}}</span>
					</div>
					<div class="cursor eqLogicAction logoSecondary" data-action="showSummary" style="color:rgb(27,161,242);">
						<i class="fas fa-tasks"></i>
						<br>
						<span style="color:var(--txt-color)">{{Vue d'ensemble}}</span>
					</div>
					<div class="cursor eqLogicAction logoSecondary" data-action="showNotifAll" style="color:rgb(27,161,242);">
						<i class="fas fa-comment-dots"></i>
						<br>
						<span style="color:var(--txt-color)">{{Config Notifier Tous}}</span>
					</div>
					<!--
						Start Generic Types
						HACK Remove when gentype config in plugin is not needed anymore
					-->
					<div class="cursor eqLogicAction logoSecondary" data-action="gotoGenTypeConfig" style="color:rgb(27,161,242);">
						<i class="fas fa-building"></i>
						<br>
						<span style="color:var(--txt-color)">{{Config types génériques}}</span>
					</div>
					<!-- End Generic Types -->
					<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
						<i class="fas fa-wrench"></i>
						<br>
						<span>{{Configuration}}</span>
					</div>
					<?php if ($hasErrorPage) { ?>
						<div class="cursor eqLogicAction" data-action="showError" style="color:red;">
							<i class="fas fa-exclamation-circle"></i>
							<br>
							<span style="color:var(--txt-color)" id="spanWidgetErreur">{{Erreur}}</span>
							<sup>
								<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Il semblerait que vous ayez quelques widgets avec de mauvaises commandes configurées (ou inexistantes).<br/>Vous pouvez les filtrer en appuyant sur ce bouton"></i>
							</sup>
						</div>
					<?php } ?>
				</div>
			</div>
			<div class="col-sm-2">
				<legend><i class="fas fa-comments"></i> {{Community}}</legend>
				<!-- Boutons de gestion du plugin -->
				<div class="eqLogicThumbnailContainer">
					<div class="cursor eqLogicAction logoSecondary" data-action="showCommunity" style="color:rgb(27,161,242);">
						<i class="fas fa-exclamation-circle"></i>
						<br>
						<span>{{Infos}}</span>
						<div style="display:none">
							<?php if ($displayWarning) { ?>
								<span class="displayJCWarning">
									Pour chacun des sujets que vous partagez sur le <a href="https://community.jeedom.com/tag/plugin-jeedomconnect" target="_blank"><span style="color:rgb(27,161,242);"> forum community</span> <i class="fas fa-external-link-alt"></i></a>
									afin de vous aider le plus facilement et rapidement possible, merci de <u><strong>systématiquement</strong></u> partager les informations
									de votre installation, qui sont disponibles en seulement un clic sur le bouton 'Community Infos' en haut à droite de la page principale de JeedomConnect (JC pour les intimes) !
									<br /><br />
									Ces informations nous permettent de savoir quelle version vous utilisez afin de mieux répondre à votre demande.

								</span>
							<?php } ?>
							<span class="txtInfoPlugin">
								Si vous avez des interrogations, postez un message sur le <a href="https://community.jeedom.com/tag/plugin-jeedomconnect" target="_blank"><span style="color:rgb(27,161,242);"> forum community</span> <i class="fas fa-external-link-alt"></i></a>
								<br /><i>après avoir vérifié que le sujet n'a pas déjà été traité !</i>
								<br /><br />Appuyez sur le bouton 'copier' en bas de la fenêtre pour récupérer l'ensemble des informations affichées, et partagez/collez-les à chaque nouveau post sur le forum !
								<br /><br />
							</span>
							<span class="infoPlugin">
								<span id="infoPlugin">
									<?= $infoPlugin; ?>
								</span>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!--   PANEL DES EQUIPEMENTS  -->
		<legend style="margin-top:10px"><i class="fas fa-mobile-alt fa-lg"></i> {{Mes appareils}}</legend>
		<!-- Champ de recherche -->
		<div class="input-group" style="margin:10px 5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
		</div>
		<!-- Liste des équipements du plugin -->
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				// echo '<div class="eqLogicDisplayCardParent">';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . JeedomConnectUtils::getCustomPathIcon($eqLogic) . '"/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span>';
				// echo '</div>';
				echo '<a class="btn btn-success btnAssistant" title="Assistant configuration"><i class="fas fa-icons"></i></a>&nbsp;';
				echo '<a class="btn btn-success btnNotification" title="Assistant notificaion"><i class="fas fa-comment-dots"></i></a>';
				echo '</span>';
				echo '</div>';
			}
			?>
		</div>
		<!--  FIN --- PANEL DES EQUIPEMENTS  -->

		<!--   PANEL DES WIDGETS  -->
		<legend><i class="fas fa-table"></i> {{Mes widgets}} <span id="coundWidget"></span>

			<div class="pull-right">
				<span style="margin-right:10px">{{Trie}}
					<select id="widgetOrder" onchange="updateOrderWidget()" style="width:100px">
						<?php
						echo $optionsOrderBy;
						?>
					</select>
				</span>
				<span>{{Filtre}}
					<select id="widgetTypeSelect" style="width:auto">
						<?php
						echo $typeSelection;
						?>
					</select>
				</span>
				<span id="eraseFilterChoice" class="btn roundedRight">
					<!-- <i class="fas fa-times"></i> -->
					<i class="fas fa-trash-alt"></i>
				</span>
			</div>
		</legend>
		<!-- Champ de recherche widget -->
		<div class="input-group" style="margin:10px 5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher sur le nom ou l'id}}" id="in_searchWidget" value="<?= $widgetSearch ?>" />
			<div class="input-group-btn">
				<a id="bt_resetSearchWidget" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
		</div>
		<!-- Liste des widgets du plugin -->
		<div class="eqLogicThumbnailContainer" id="widgetsList-div">
			<?php
			echo $listWidget;
			?>
		</div>
	</div> <!-- /.eqLogicThumbnailDisplay -->
	<!--  FIN ---  PANEL DES WIDGETS  -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i><span class="hidden-xs"> {{Équipement}}</span></a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i><span class="hidden-xs"> {{Commandes}}</span></a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-7 jeedomConnect">
							<legend><i class="fas fa-wrench"></i> {{Général}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'appareil}}</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Objet parent}}</label>
								<div class="col-sm-7">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-9">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Options}}</label>
								<div class="col-sm-7">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>

							<div class="form-group" style="display:none;">
								<label class="col-sm-3 control-label">{{Type}}</label>
								<div class="col-sm-7">
									<select id="sel_type" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type">
										<option value="mobile">mobile</option>
									</select>
								</div>
							</div>
							<br>

							<legend><i class="fa fa-cogs"></i> {{Paramètres}}</legend>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Activer la connexion par Websocket}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="useWs" type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Activer le polling}}
									<sup>
										<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Cette option est plus que recommandée si vous utilisez les DNS Jeedom.<br/>(incompatible avec l'option websocket)"></i>
									</sup>
								</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="polling" type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Utilisateur}}</label>
								<div class="col-sm-7">
									<select class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="userId">
										<option value="">{{Aucun}}</option>
										<?php
										foreach (user::all() as $user) {
											echo '<option value="' . $user->getId() . '">' . $user->getLogin() . '</option>';
										}
										?>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Mot de passe}}
									<sup>
										<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Vous avez la possibilité d'utiliser un mot de passe alphanumérique pour confirmer une action<br>Il est nécessaire de le définir ici pour s'en servir dans l'application."></i>
									</sup>
								</label>
								<div class="col-sm-6 pass_show">
									<input id="actionPwd" type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="pwdAction" placeholder="{{Mot de passe pour confirmer une action sur l'application}}" />
									<span toggle="#password-field" class="eye fa fa-fw fa-eye field_icon toggle-password"></span>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Assistant}}</label>
								<div class="col-sm-7">
									<a class="btn btn-success" id="assistant-btn"><i class="fa fa-wrench"></i> {{Configurer l'appareil}}
									</a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Configuration de l'équipement}}</label>
								<div class="col-sm-7 input-group" style="display:inline-flex;">
									<span class="input-group-btn">
										<input type="file" accept=".json" id="import-input" style="display:none;">
										<a class="btn btn-warning" id="export-btn"><i class="fa fa-save"></i> {{Exporter}}</a>
										<a class="btn btn-primary" id="import-btn"><i class="fa fa-cloud-upload-alt"></i> {{Importer}}</a>
										<a class="btn btn-default" id="copy-btn"><i class="fas fa-copy"></i> {{Copier vers}}</a>
										&nbsp;&nbsp;<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Partagez votre configuration sur un autre équipement"></i>
									</span>

								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Appareil enregistré}}</label>
								<div class="col-sm-7" style="display:inline-flex;">
									<span class="eqLogicAttr label" style="font-size:1em!important;margin-right:5px;" data-l1key="configuration" type="text" data-l2key="deviceName"></span>
									<a class="btn btn-danger" id="removeDevice"><i class="fa fa-minus-circle"></i> {{Détacher}} </a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Notifications}}</label>
								<div class="col-sm-7">
									<a class="btn btn-success" id="notifConfig-btn"><i class="fa fa-wrench"></i> {{Configurer}}
									</a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Accès scénarios}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="scenariosEnabled" type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Accès à la timeline}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timelineEnabled" checked type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Accès Interface web}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="webviewEnabled" type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Ajouter données à la position}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="addAltitude" type="checkbox" placeholder="{{}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Masquer la batterie sur page Equipement Jeedom}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="hideBattery" type="checkbox">
								</div>
							</div>


							<legend><i class="fa fa-bug"></i> {{Partager le fichier de configuration}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Debug Configuration}}</label>
								<div class="col-sm-7 input-group" style="display:inline-flex;">
									<span class="input-group-btn">
										<a class="btn btn-default" id="exportAll-btn"><i class="fa fa-file-export"></i> {{Partager}}</a>
										&nbsp;&nbsp;<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="A la demande du développeur, partagez votre fichier de configuration finale"></i>
									</span>
								</div>
							</div>

						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche l'icône du plugin par défaut mais vous pouvez y afficher les informations de votre choix -->
						<div class="col-lg-5">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<div class="alert alert-info" style="width:300px; margin: 10px auto;text-align:center;">
									Utilisez l'assistant de configuration pour gérer l'interface de l'application.<br />
									Dans la partie Login de l'application, scannez directement le QR Code.
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Clé API :}}</label>
								<div class="col-sm-9">
									<span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="configuration" type="text" data-l2key="apiKey"></span>

									<!-- <a class="btn btn-default form-control btRegenerateApiKey roundedRight" style="width:30px"><i class="fas fa-sync"></i></a> -->
									<a class="btRegenerateApiKey" style="padding-left:10px" title="Regénérer la clé API de cet équipement"><i class="fas fa-sync"></i></a>
									<sup>
										<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Regénérer la clé API de cet équipement"></i>
									</sup>

								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{QR Code :}}</label>
								<div class="row">
									<div class="col-sm-6">
										<img id="img_config" class="img-responsive" style="margin:10px auto; max-height : 250px;" />
									</div>
								</div>

								<div class="row" style="margin: 0px auto;text-align:center;">
									<span class="eqNameQrCode" style="font-size:1.2em;font-weight: bold;"></span>
								</div>
								<div class="row" style="margin: 10px auto;text-align:center;">
									<a class="btn btn-infos" id="qrcode-regenerate"><i class="fa fa-qrcode"></i> {{Regénérer QR Code}}</a>
								</div>

								<div class="row">
									<div class="alert alert-danger" style=" margin: 10px auto; width:350px;">
										Veuillez re-générer le QR code si vous avez modifié :<br />
										<ul>
											<li>Les adresses dans la config du plugin</li>
											<li>L'utilisateur de cet équipement</li>
											<li>La connexion websocket de cet équipement</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
				<hr>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!-- <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a> -->
				<br /><br />
				<div class="table-responsive">

					<div class="col-lg-6">
						<legend><i class="fas fa-info-circle"></i> {{Commandes de type info}}</legend>
					</div>
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="width: 50px;">#</th>
								<th style="width: 300px;">{{Nom}}</th>
								<th style="width: 160px;">{{Sous-type}}</th>
								<th style="width: 200px;">{{Valeur}}</th>
								<th style="width: 100px;">{{Options}}</th>
								<th style="width: 50px;">{{Ordre}}</th>
								<th style="width: 100px;"></th>
							</tr>
						</thead>
						<tbody class="cmd_info">
						</tbody>
					</table>
				</div>
				<br /><br />
				<div class="table-responsive">
					<div class="col-lg-6">
						<legend><i class="fas fa-play-circle"></i> {{Commandes de type action}}</legend>
					</div>
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="width: 50px;">#</th>
								<th style="width: 300px;">{{Nom}}</th>
								<th style="width: 160px;">{{Sous-type}}</th>
								<th style="width: 200px;">{{Valeur}}</th>
								<th style="width: 100px;">{{Options}}</th>
								<th style="width: 50px;">{{Ordre}}</th>
								<th style="width: 100px;"></th>
							</tr>
						</thead>
						<tbody class="cmd_action">
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->

</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'JeedomConnect', 'css', 'JeedomConnect'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>