
<table id="items_count_details" class="table table-striped table-hover">
	<thead>
		<tr style="background-color: #999 !important;">
			<th colspan="4">Counter Items List</th>
		</tr>
		<tr>
			<th width="10%">Id.</th>
			<th width="30%">Category</th>
			<th width="60%">Item Name</th>
		</tr>
	</thead>
	<tbody>
		<?php 
			foreach ($item_info as $item) {
		?>
			<tr>
				<td><?php echo $item['item_id'] ?></td>
				<td><?php echo $item['category'] ?></td>
				<td><?php echo $item['name'] ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>