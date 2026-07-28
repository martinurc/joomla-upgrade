<?php
/**
 * Layout variables
 *
 * @var array $errors
 */
?>
<ul class="eb-validation_errors">
	<?php
		foreach ($errors as $error)
		{
		?>
			<li><?php echo $error; ?></li>
		<?php
		}
	?>
</ul>
