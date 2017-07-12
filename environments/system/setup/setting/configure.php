<h2 class="page-title text-primary">Environment Configuration</h2>
<?php
	$configuration = ENVIRONMENT::configuration();
	$environments  = ENVIRONMENT::details("*");
	if($configuration["environments"]):
?>
<div class="row">
	<div class="col-sm-12">
		<table class="table table-striped">
			<thead class="info">
				<tr>
					<th>Priority</th>
					<th>Environment</th>
					<th>Direct</th>
					<th>Methods</th>
				</tr>
			</thead>
			<tbody>
				<?php
				 	$i = 1;
					foreach ($configuration["priority"] as $namespace) :
				?>
				<tr>
					<td><?php echo $i; $i++;?></td>
					<td><?php echo $environments[$namespace]["details"]["name"];?> <small><?php echo $environments[$namespace]["details"]["version"];?> </small></td>
					<td><?php echo $configuration["environments"][$namespace]["direct"]?"true":"false";?> </td>
					<td><?php echo implode(",",array_diff($environments[$namespace]["methods"],array_diff($environments[$namespace]["methods"],$configuration["methods"])));?> </td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php else:?>
	<div class="callout callout-danger">
		<h4>No Environment Installed</h4>
		<p>Please Install some environments.</p>
	</div>
<?php endif;?>
