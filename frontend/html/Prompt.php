<?php
use packages\base;
use packages\userpanel;
$this->the_header('login');
?>
<div class="box-login">
	<?php
	if ($this->app->logo) {
		echo "<img src=\"" . $this->app->getLogoURL() . "\" class=\"app-logo\" alt=\"{$this->app->name}\">";
	} else {
		echo "<i class=\"fa fa-rocket app-logo\"></i>";
	}
	?>
	<p>برنامه <strong><?php echo $this->app->name; ?></strong> قصد دارد تا به اکانت کاربری شما در این سایت دسترسی پیدا کند.</p>
	<form class="form-login" name="form-login" action="<?php echo userpanel\url("oauth2/authorize", array(
		'response_type' => 'code',
		'redirect_uri' => $this->getRedirect(),
		'client_id' => $this->getApp()->token,
		'state' => $this->getState(),
	)); ?>" method="post">
		<p class="text-center">
			<button type="submit" class="btn btn-green"><i class="fa fa-check"></i> اتصال را برقرار کن</button>
			<a href="<?php echo $this->getRejectRedirect(); ?>" class="btn btn-light-grey"><i class="fa fa-times"></i> مخالفم</a>
		</p>
	</form>
</div>
<?php $this->the_footer('login'); ?>
