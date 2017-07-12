<?php
	if(isset($_REQUEST["action"])){
		switch ($_REQUEST["action"]) {
			case 'new': require_once __DIR__."/configure-new.php"; break;
			case 'edit': require_once __DIR__."/configure-edit.php"; break;
			case 'activate': require_once __DIR__."/configure-activate.php"; break;
			case 'build': require_once __DIR__."/configure-build.php"; break;
			default: header("Location: ".ENVIRONMENT::url()->current()."?".http_build_query([ "page" =>	"configure", ])); break;
		}
		die();
	}
?>
<h2 class="page-heading text-primary">
	Apps Configuration
	<a href="?<?php echo http_build_query([
		"page"	=>	"configure",
		"action"=>	"new",
		])?>" class="btn btn-success pull-right"><span class="glyphicon glyphicon-plus"> </span>New</a>
</h2>
<?php
	$active 	= \maple\app\APP::apps("active");
	$all		= \maple\app\APP::apps("*");
	$available	= array_diff_key( $all, $active);
	if(isset($_REQUEST["error"])):
?>
<div class="callout callout-danger">
	<h4>An Error Occured!</h4>
	<p><?php echo $_REQUEST["error"]?></p>
</div>
<?php endif;?>
<div class="row">
	<div class="col-sm-12">
		<hr>
		<h4>Active Sites</h4>
		<?php if($active):?>
			<table class="table">
				<thead>
					<th>App Name</th>
					<th>App Environment</th>
					<th>Folder</th>
					<th></th>
				</thead>
				<tbody>
					<?php foreach ($active as $folder => $data):?>
					<tr>
						<td><?php echo $data["name"] ?></td>
						<td><?php echo $data["environment"] ?></td>
						<td><?php echo $folder ?></td>
						<td><a href="?<?php echo http_build_query([
							"page"	=>	"configure",
							"action"=>	"edit",
							"edit"	=>	$folder
							])?>" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-pencil"> </span> Edit</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else:?>
			<div class="callout callout-warning">
				<h4>No Active Site</h4>
				<p>It seems that you do not have an active website.Please create a new configuration or add a packages folder that supports maple environment namespace "maple/app"</p>
			</div>
		<?php endif;?>
	</div>
	<div class="col-sm-12">
		<hr>
		<h4>Available Sites</h4>
		<?php if($available):?>
			<table class="table">
				<thead>
					<th>Name</th>
					<th>Url</th>
					<th>Folder</th>
					<th></th>
				</thead>
				<tbody>
					<?php foreach ($available as $folder => $data):?>
					<tr>
						<td><?php echo $data["name"] ?></td>
						<td><?php echo \maple\environments\eAPP::url("root").$data["url"] ?></td>
						<td><?php echo $folder ?></td>
						<td><a href="?<?php echo http_build_query([
							"page"	=>	"configure",
							"action"=>	"activate",
							"activate"	=>	$folder
							])?>" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-plus"> </span> Activate</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else:?>
			<div class="callout callout-warning">
				<h4>No Available Site</h4>
				<p>It seems that you do not have any other available website.</p>
			</div>
		<?php endif;?>
		<hr>
	</div>
</div>
