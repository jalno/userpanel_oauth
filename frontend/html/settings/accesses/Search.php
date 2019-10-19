<?php
use packages\base\{view\Error, json, frontend\theme};
use packages\userpanel;
use packages\userpanel\Date;
use packages\userpanel_oauth\Access;
use themes\clipone\utility;
$this->the_header();
?>
<div class="row">
<?php if ($this->canAdd) { ?>
	<div class="col-lg-4">
		<div class="panel panel-default panel-accesses">
			<div class="panel-heading">
				<i class="fa fa-plus"></i> <?php echo t("userpanel_oauth.accesses.add"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="accesses-add" action="<?php echo userpanel\url("settings/accesses/add"); ?>" method="POST">
					<?php
					$this->createField(array(
						"type" => "select",
						"name" => "app",
						"label" => t("userpanel_oauth.access.app"),
						"options" => $this->getAppsForSelect(),
					));
					if ($this->multiuser) {
						$this->createField(array(
							"name" => "user",
							"type" => "hidden",
						));
						$this->createField(array(
							"name" => "user_name",
							"label" => t("userpanel_oauth.access.user"),
						));
					}
					$this->createField(array(
						"type" => "select",
						"name" => "status",
						"label" => t("userpanel_oauth.access.status"),
						"options" => $this->getStatusForSelect(),
					));
					?>
				</form>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-6 col-lg-offset-6 col-md-6 col-md-offset-6 col-sm-8 col-sm-offset-2 col-xs-12">
						<button type="submit" form="accesses-add" class="btn btn-success btn-submit btn-block">
							<div class="pull-right"> <i class="fa fa-plus" style="vertical-align: middle;"></i> </div>
						<?php echo t("userpanel.add"); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
	<div class="<?php echo $this->canAdd ? "col-lg-8 " : ""; ?>col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-id-card-o"></i> <?php echo t("userpanel_oauth.accesses"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t("userpanel.search"); ?>" data-toggle="modal" href="#accesses-search"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
			<?php if ($accesses = $this->getDataList()) { ?>
				<div class="table-responsive">
					<table class="table table-hover table-accesses" data-can-edit="<?php echo json\encode($this->canEdit); ?>" data-can-delete="<?php echo json\encode($this->canDelete); ?>">
					<?php $hasButtons = $this->hasButtons(); ?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo t("userpanel_oauth.access.app"); ?></th>
							<?php if($this->multiuser){ ?>
								<th class="user-th"><?php echo t("userpanel_oauth.access.user"); ?></th>
							<?php } ?>
								<th><?php echo t("userpanel_oauth.access.lastuse"); ?></th>
								<th><?php echo t("userpanel_oauth.access.status"); ?></th>
							<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($accesses as $access) { ?>
							<tr id="access-<?php echo $access->id; ?>" data-access='<?php echo json\encode($access->toArray(true)); ?>'>
								<td class="center"><?php echo $access->id; ?></td>
								<td><?php
									if ($access->app->logo) {
										echo "<img src=\"" . $access->app->getLogoURL() . "\" class=\"app-logo\" alt=\"{$access->app->name}\">";
									} else {
										echo "<i class=\"fa fa-rocket app-logo\"></i>";
									}
									echo $access->app->name;
								?></td>
							<?php if($this->multiuser){ ?>
								<td><a href="<?php echo userpanel\url("users", array("id" => $access->user->id)); ?>" class="tootips" title="#<?php echo $access->user->id; ?>" target="_blank"><?php echo $access->user->getFullName(); ?></a></td>
							<?php } ?>
								<td class="center ltr"><?php echo $access->lastuse_at ? Date::format("Y/m/d<br>H:i:s", $access->lastuse_at) . "<br>" . $access->lastip : "-"; ?></td>
								<td><?php echo utility::switchcase($access->status, [
									'<span class="label label-success">' . t("userpanel_oauth.access.status.active") . '</span>' => Access::ACTIVE,
									'<span class="label label-danger">' . t("userpanel_oauth.access.status.deactive") . '</span>' => Access::DEACTIVE,
								]);
								?></td>
							<?php
							if ($hasButtons) {
								echo("<td class=\"center\">".$this->genButtons(array("accesses_delete"))."</td>");
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
					<h4 class="alert-heading"><i class="fa fa-info-circle"></i> <?php echo t("error." . error::NOTICE . ".title"); ?></h4>
					<?php echo t("error.userpanel_oauth.accesses.notfound"); ?>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="accesses-search" tabindex="-1" data-show="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel.search"); ?></h4>
	</div>
	<div class="modal-body">
		<form id="accesses-search-form" action="<?php echo userpanel\url("settings/accesses"); ?>" method="GET" class="form-horizontal">
			<?php
			$this->setHorizontalForm("sm-3","sm-9");
			$feilds = array(
				array(
					"label" => t("userpanel_oauth.access.id"),
					"name" => "id",
					"ltr" => true,
				),
				array(
					"type" => "select",
					"label" => t("userpanel_oauth.access.app"),
					"name" => "app",
					"options" => array_merge([array(
						"title" => t("userpanel.choose"),
						"value" => "",
					)], $this->getAppsForSelect()),
				),
				array(
					"type" => "select",
					"name" => "status",
					"label" => t("userpanel_oauth.access.status"),
					"options" => array_merge([
					array(
						"title" => t("userpanel.choose"),
						"value" => "",
					)], $this->getStatusForSelect()),
				),
				array(
					"type" => "select",
					"label" => t("search.comparison"),
					"name" => "comparison",
					"options" => $this->getComparisonsForSelect(),
				),
			);
			if ($this->multiuser) {
				$userSearch = array(
					array(
						"name" => "user",
						"type" => "hidden"
					),
					array(
						"name" => "user_name",
						"label" => t("userpanel_oauth.access.user")
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
		<button type="submit" form="accesses-search-form" class="btn btn-success"><?php echo t("userpanel.search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("userpanel.cancel"); ?></button>
	</div>
</div>
<div class="modal fade" id="access-delete" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel_oauth.accesses.delete"); ?> #<span class="access-id"></span></h4>
	</div>
	<div class="modal-body">
		<form id="access-delete-form">
			<div class="alert alert-warning">
				<h4 class="alert-heading"> <i class="fa fa-exclamation-triangle"></i> <?php echo t("error." . error::WARNING . ".title"); ?> </h4>
			<?php echo t("userpanel_oauth.accesses.delete.warning"); ?>
			</div>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="access-delete-form" class="btn btn-danger"><?php echo t("userpanel_oauth.delete"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("userpanel_oauth.cance"); ?></button>
	</div>
</div>

<div class="modal fade" id="access-show" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("userpanel_oauth.access.code"); ?></h4>
	</div>
	<div class="modal-body">
		<div class="image-with-check">
			<img src="<?php echo theme::url('assets/images/default-app.png'); ?>" class="img-responsive app-logo" data-default="<?php echo theme::url('assets/images/default-app.png'); ?>">
			<i class="fa fa-check-circle"></i>
		</div>
		<p><?php echo t("userpanel_oauth.access.secret.warning"); ?></p>
		<?php
		$this->createField(array(
			"name" => "code",
			"label" => t("userpanel_oauth.access.code"),
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
			"name" => "token",
			"label" => t("userpanel_oauth.access.token"),
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
