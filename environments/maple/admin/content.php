<?php foreach (ADMIN::getDashCards() as $card):?>
<div class="col s12 l6">
	<div class="card-panel">
		<?php call_user_func($card)?>
	</div>
</div>
<?php endforeach;?>
