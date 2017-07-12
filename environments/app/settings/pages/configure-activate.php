<?php
$config = \maple\www\WWW::configuration($_REQUEST["activate"]);
$actives = \maple\www\WWW::sites("active");
if(in_array($_REQUEST["activate"],array_keys($actives))) { header("Location: ".ENVIRONMENT::url()->current()."?".http_build_query([ "page" =>	"configure"])); die(); }
if(!$config){
	header("Location: ".ENVIRONMENT::url()->current()."?".http_build_query([ "page" =>	"configure","error"=>"Invalid Site folder" ]));
	die();
}
?>
<h2 class="text-primary">Activate Website - <?php echo $_REQUEST["activate"]?></h2>
<form action="<?php echo \maple\environments\eWWW::url("settings")?>" method="post">
	<div class="panel">
		<div class="panel-body">
			<table class="table">
				<tbody>
					<tr> <td><strong>Name : </strong></td><td><?php echo $config["name"]?></td> </tr>
					<tr> <td><strong>Version : </strong></td><td><?php echo $config["details"]["version"]?></td> </tr>
					<tr> <td><strong>Author : </strong></td><td><?php echo $config["details"]["author"]?></td> </tr>
					<tr> <td><strong>Folder : </strong></td><td><?php echo $_REQUEST["activate"]?></td> </tr>
					<tr> <td><strong>Description : </strong></td><td><?php echo $config["details"]["description"]?></td> </tr>
					<tr> <td><strong>Required Environments : </strong></td><td><?php echo isset($config["requires"])?implode(",",array_keys($config["requires"])):"-"; ?></td></tr>
				</tbody>
			</table>
			<hr>
			<table class="table">
				<tbody>
					<tr>
						<td><strong>Url:</strong></td>
						<td>
							<div class="input-group">
								<span class="input-group-addon" id="basic-addon1.1"><span class="glyphicon glyphicon-globe"></span></span>
								<span class="input-group-addon" id="basic-addon1.2"><?php echo \maple\environments\eWWW::url("root")?></span>
								<input type="text" class="form-control" placeholder="url" aria-describedby="basic-addon1.1" name="url" value="<?php echo $config["url"]?>" autofocus="true">
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="panel-footer">
			<input type="text" name="location" value="<?php echo $_REQUEST["activate"]?>" hidden="true">
			<input type="text" name="www-ajax-action" value="activate-site" hidden="true">
			<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok"></span>Activate</button>
		</div>
	</div>
</form>
