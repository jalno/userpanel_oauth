<?php
use packages\base\{view\Error, frontend\theme, json};
use packages\userpanel;
use packages\userpanel_oauth\App;
use themes\clipone\utility;

$this->the_header();
?>
<div class="row">
<?php if ($this->canAdd) { ?>
	<div class="col-lg-4">
		<div class="panel panel-default panel-apps">
			<div class="panel-heading">
				<i class="fa fa-plus"></i> <?php echo t("userpanel_oauth.apps.add"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="apps-add" method="POST" action="<?php echo userpanel\url("settings/apps/add"); ?>" enctype="multipart/form-data">
					<div class="form-group">
						<div class="app-logo-preview avatarPreview">
							<img src="<?php echo theme::url('assets/images/default-app.png'); ?>" class="preview img-responsive">
							<input name="logo" type="file">
							<div class="button-group">
								<button type="button" class="btn btn-teal btn-sm btn-upload"><i class="fa fa-pencil"></i></button>
								<button type="button" class="btn btn-bricky btn-sm btn-remove" data-default="<?php echo theme::url('assets/images/default-app.png'); ?>"><i class="fa fa-times"></i></button>
							</div>
						</div>
					</div>
					<?php
					$this->createField(array(
						"name" => "name",
						"label" => t("userpanel_oauth.app.name"),
					));
					$this->createField(array(
						"name" => "ip",
						"label" => t("userpanel_oauth.app.ip"),
						"ltr" => true,
					));
					if ($this->multiuser) {
						$this->createField(array(
							"name" => "user",
							"type" => "hidden",
						));
						$this->createField(array(
							"name" => "user_name",
							"label" => t("userpanel_oauth.app.user"),
						));
					}
					$this->createField(array(
						"type" => "select",
						"label" => t("userpanel_oauth.app.status"),
						"name" => "status",
						"options" => $this->getStatusForSelect()
					));
					?>
				</form>
			</div>
			<div class="panel-footer">
				<div class="btn-group pull-left">
					<button type="button" class="btn btn-default btn-reset">
						<div class="pull-right"> <i class="fa fa-times" style="vertical-align: middle;"></i> </div>
						<?php echo t("userpanel.cancel"); ?>
					</button>
					<button type="submit" form="apps-add" class="btn btn-success btn-submit">
						<div class="pull-right"> <i class="fa fa-plus" style="vertical-align: middle;"></i> </div>
					<?php echo t("userpanel.add"); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
	<div class="<?php echo $this->canAdd ? "col-lg-8" : ""; ?> col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-id-card-o"></i> <?php echo t("userpanel_oauth.apps"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t("userpanel.search"); ?>" data-toggle="modal" href="#apps-search"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
			<?php if ($apps = $this->getDataList()) { ?>
				<div class="table-responsive">
					<table class="table table-hover table-apps" data-can-edit="<?php echo json\encode($this->canEdit); ?>" data-can-delete="<?php echo json\encode($this->canDelete); ?>">
					<?php $hasButtons = $this->hasButtons(); ?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th class="center"><?php echo t("userpanel_oauth.app.name"); ?></th>
							<?php if($this->multiuser){ ?>
								<th class="user-th"><?php echo t("userpanel_oauth.app.user"); ?></th>
							<?php } ?>
								<th><?php echo t("userpanel_oauth.app.ip"); ?></th>
								<th><?php echo t("userpanel_oauth.app.status"); ?></th>
							<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php foreach($apps as $app) { ?>
							<tr id="app-<?php echo $app->id; ?>" data-app='<?php echo json\encode($app->toArray()); ?>'>
								<td class="center"><?php echo $app->id; ?></td>
								<td><?php
									if ($app->logo) {
										echo "<img src=\"" . $app->getLogoURL() . "\" class=\"app-logo\" alt=\"{$app->name}\">";
									} else {
										echo "<i class=\"fa fa-rocket app-logo\"></i>";
									}
									echo $app->name;
								?></td>
								<?php if($this->multiuser){ ?>
									<td><a href="<?php echo userpanel\url("users", array("id" => $app->user->id)); ?>" target="_blank"><?php echo $app->user->getFullName(); ?></a></td>
								<?php } ?>
								<td class="ltr center"><?php echo $app->ip ?? "<i class=\"fa fa-times-circle-o\"></i>"; ?></td>
								<td><?php echo utility::switchcase($app->status, [
									'<span class="label label-success">' . t("userpanel_oauth.app.status.active") . '</span>' => App::ACTIVE,
									'<span class="label label-danger">' . t("userpanel_oauth.app.status.deactive") . '</span>' => App::DEACTIVE,
								]);
								?></td>
							<?php
							if ($hasButtons) {
								echo("<td class=\"center\">".$this->genButtons(array("apps_edit", "apps_delete"))."</td>");
							}
							?>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<?php
					$this->paginator();
				} else {
				?>
				<div class="alert alert-info">
					<h4 class="alert-heading"><i class="fa fa-info-circle"></i> <?php echo t("error." . Error::NOTICE . ".title"); ?></h4>
					<?php echo t("error.userpanel_oauth.apps.notfound"); ?>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="apps-search" tabindex="-1" data-show="true" role="diaapp">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel.search"); ?></h4>
	</div>
	<div class="modal-body">
		<form id="apps-search-form" action="<?php echo userpanel\url("settings/apps"); ?>" method="GET" class="form-horizontal">
			<?php
			$this->setHorizontalForm("sm-3","sm-9");
			$feilds = array(
				array(
					"label" => t("userpanel_oauth.app.id"),
					"name" => "id",
					"ltr" => true,
				),
				array(
					"label" => t("userpanel_oauth.app.name"),
					"name" => "title",
				),
				array(
					"label" => t("userpanel_oauth.app.token"),
					"name" => "token",
					"ltr" => true,
				),
				array(
					"type" => "select",
					"label" => t("search.comparison"),
					"name" => "comparison",
					"options" => $this->getComparisonsForSelect()
				)
			);
			if ($this->multiuser) {
				$userSearch = array(
					array(
						"name" => "search_user",
						"type" => "hidden"
					),
					array(
						"name" => "search_user_name",
						"label" => t("userpanel_oauth.app.user")
					)
				);
				array_splice($feilds, 2, 0, $userSearch);
			}
			foreach ($feilds as $input) {
				$this->createField($input);
			}
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="apps-search-form" class="btn btn-success"><?php echo t("userpanel.search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("userpanel.cancel"); ?></button>
	</div>
</div>
<div class="modal fade" id="app-delete" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel_oauth.apps.delete"); ?> #<span class="app-id"></span></h4>
	</div>
	<div class="modal-body">
		<form id="delete-apps">
			<div class="alert alert-warning">
				<h4 class="alert-heading"> <i class="fa fa-exclamation-triangle"></i> <?php echo t("error." . error::WARNING . ".title"); ?> </h4>
			<?php echo t("userpanel_oauth.apps.delete.warning"); ?>
			</div>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="delete-apps" class="btn btn-danger">حذف</button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("userpanel_oauth.cancel"); ?></button>
	</div>
</div>
<div class="modal fade" id="app-show" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel_oauth.app.secret"); ?></h4>
	</div>
	<div class="modal-body">
		<div class="image-with-check">
			<img src="<?php echo theme::url('assets/images/default-app.png'); ?>" class="img-responsive app-logo" data-default="<?php echo theme::url('assets/images/default-app.png'); ?>">
			<i class="fa fa-check-circle"></i>
		</div>
		<p><?php echo t("userpanel_oauth.apps.secret.warning"); ?></p>
		<?php
		$this->createField(array(
			"name" => "token",
			"label" => t("userpanel_oauth.app.token"),
			"ltr" => true,
			"input-group" => array(
				"right" => array(
					array(
						"type" => "button",
						"icon" => "fa fa-clipboard",
						"class" => "btn btn-default btn-copy",
						"text" => ""
					),
				),
			),
		));
		$this->createField(array(
			"name" => "secret",
			"label" => t("userpanel_oauth.app.secret"),
			"ltr" => true,
			"input-group" => array(
				"right" => array(
					array(
						"type" => "button",
						"icon" => "fa fa-clipboard",
						"class" => "btn btn-default btn-copy",
						"text" => ""
					),
				),
			),
		));
		?>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-success" data-dismiss="modal" aria-hidden="true"><?php echo t("userpanel_oauth.understood"); ?></button>
	</div>
</div>
<?php
$this->the_footer();
