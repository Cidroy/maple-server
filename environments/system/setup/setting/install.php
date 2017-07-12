<?php
$_REQUEST["selected"] = isset($_REQUEST["selected"])?$_REQUEST["selected"]:false;
if($_REQUEST["selected"] && $_REQUEST["action"] == "activate" ):
?>
	<?php
		$environment = ENVIRONMENT::details($_REQUEST["selected"]);
		if(!$environment || $environment["active"]) header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([ "page"	=>	"install" ]));
	?>
	<h2 class="text-primary">Activate Environment - <strong><?php echo $environment["details"]["name"] ?></strong> <small>v<?php echo $environment["details"]["version"] ?></small> </h2>
	<p><em>by <?php echo $environment["details"]["author"] ?></em></p>
	<p><?php echo $environment["details"]["description"] ?></p>
	<form action="<?php echo ENVIRONMENT::url()->root(false).ENVIRONMENT::$url_control_panel; ?>" method="post">
		<div class="panel panel-primary">
			<div class="panel-heading">
			</div>
			<div class="panel-body">
					<h3 class="panel-title"><u>Allow Methods</u></h3>
				<div class="row">
					<?php foreach( $environment["methods"] as $method ): ?>
					<div class="col-sm-12 col-md-6">
						<div class="checkbox">
							<label >
								<input type="checkbox" name="method[]" value="<?php echo $method?>" checked="true">
								<?php echo $method?>
							</label>
						</div>
					</div>
					<?php endforeach; ?>
					<input type="text" name="environment" value="<?php echo $environment["namespace"] ?>" hidden="true">
					<input type="text" name="environment-ajax-action" value="activate" hidden="true">
				</div>
				<?php
					$_environments = ENVIRONMENT::details("*");
					$environments = [];
					foreach ($_environments as $e) {
						if($e["active"]) $environments[$e["namespace"]] = $e;
					}
					if($environments):
				?>
				<h3 class="panel-title"><u>Environment Priority</u></h3>
				<div class="row">
					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-6">
								<label class="radio-inline">
									<input type="radio" name="set" id="inlineRadio1" value="before"> Before
								</label>
							</div>
							<div class="col-sm-6">
								<label class="radio-inline">
									<input type="radio" name="set" id="inlineRadio2" value="after" checked="true"> After
								</label>
							</div>
						</div>
					</div>
					<div class="col-sm-12">
						<select class="form-control" name="reference">
							<?php foreach ($environments as $e) echo "<option value=\"{$e["namespace"]}\">{$e["namespace"]}</option>"; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>
			</div>
			<div class="panel-footer">
				<button type="submit" class="btn btn-primary">Activate</button>
			</div>
		</div>
	</form>
<?php elseif($_REQUEST["selected"] && $_REQUEST["action"] == "deactivate"): ?>
	<?php
		$environment = ENVIRONMENT::details($_REQUEST["selected"]);
		if(!$environment || !$environment["active"]) header("Location: ".\ENVIRONMENT::url()->root(false).\ENVIRONMENT::$url_control_panel."?".http_build_query([ "page"	=>	"install" ]));
	?>
	<h2 class="text-primary">Deactivate Environment - <strong><?php echo $environment["details"]["name"] ?></strong> <small>v<?php echo $environment["details"]["version"] ?></small> </h2>
	<p><em>by <?php echo $environment["details"]["author"] ?></em></p>
	<p><?php echo $environment["details"]["description"] ?></p>
	<form action="<?php echo ENVIRONMENT::url()->root(false).ENVIRONMENT::$url_control_panel; ?>" method="post">
		<div class="panel panel-danger">
			<div class="panel-heading"> </div>
			<div class="panel-body">
				<p>Are you sure you want to disable this environment?</p>
				<input type="text" name="environment-ajax-action" value="deactivate" hidden="true">
				<input type="text" name="environment" value="<?php echo $_REQUEST["selected"]?>" hidden="true">
			</div>
			<div class="panel-footer">
				<button type="submit" class="btn btn-danger">Yes!</button>
				<a href="<?php echo ENVIRONMENT::url()->root(false).ENVIRONMENT::$url_control_panel."?".http_build_query([ "page"	=>	"install" ]) ?>" class="btn btn-success pull-right" >No!!</a>
			</div>
		</div>
	</form>
<?php else: ?>
	<h2 class="text-primary">Install Environment</h2>
	<?php if(isset($_REQUEST["message"])):?>
		<div class="callout callout-danger">
			<h4>An Error Occured : <label class="text-primary"><?php echo $_REQUEST["message"] ?></label></h4>
		</div>
	<?php endif;?>
	<?php
		foreach (ENVIRONMENT::details("*") as $environment):
	?>
		<div class="panel panel-primary">
			<div class="panel-body">
				<h4 class="text-warning"><?php echo $environment["details"]["name"] ?> <small>v<?php echo $environment["details"]["version"] ?></small></h4>
				<p><em>by <?php echo $environment["details"]["author"] ?></em></p>
				<p><?php echo $environment["details"]["description"] ?></p>
				<?php if($environment["active"]): ?>
					<a href="?<?php echo http_build_query(["page"=>"install","selected"=>$environment["namespace"],"action"=>"deactivate"])?>" class="btn btn-danger">Deactivate</a>
				<?php else:?>
					<a href="?<?php echo http_build_query(["page"=>"install","selected"=>$environment["namespace"],"action"=>"activate"])?>" class="btn btn-success">Activate</a>
				<?php endif;?>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
