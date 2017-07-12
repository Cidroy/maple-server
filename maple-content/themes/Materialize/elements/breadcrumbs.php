<ol class="breadcrumb">
	<?php foreach (BREADCRUMBS::Get() as $name => $url):?>
	<li><a href="<?php echo $url?>"><?php echo $name;?></a></li>
	<?php endforeach;?>
</ol>