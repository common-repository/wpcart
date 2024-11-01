<?php 
//Print the credit card information
//global $payments;
?>
<br />
<h2>Credit Card Report</h2>
<form method="post" action="?page=shopcart_reports&report=creditcard">
Select Start Date:<input type="text" name="startdate" value="<?php print $startdate; ?>"  /><br />
Select End Date: <input type="text" name="enddate"  value="<?php print $enddate; ?>" /><br />
Detail <input type="radio" name="rpttype" value="detail" <?php echo $_POST['rpttype'] == 'detail' ? 'checked' : '';?> />
Summary <input type="radio" name="rpttype" value="summary" <?php echo $_POST['rpttype'] == 'summary' ? 'checked' : '';?>/><br />
<input type="submit" name="runreport" value="Run Report" />
<!-- <input type="hidden" name="report" value="creditcard" /> -->
</form>

<table border="1">
<thead><d>
<td>Type</td>
<td>Name</td>
<td>Number</td>
<td>App. Code</td>
<td>Amount</td>
</tr></thead>
<?php
$group=array('cc_type');
$firsttime=true;
foreach ($payments as $payline) {
	if ($firsttime) {
		$firsttime = false;
	} else {
		foreach ($group as $field) {
			if ($payline->$field != $previousrow->$field)  {
				print '<tr>';
				print '<td colspan="4" align="right">';
				print "Total of {$count[$field]} {$previousrow->$field}";
				print '</td>';
				print '<td align="right">$';
				print number_format ($total[$field],2);
				print '</td>';
				print '</tr>';
				//We have a new grouping
				$total[$field] = $count[$field] = 0;
			}
		}
	}
	if ($_POST['rpttype']=='detail') {
		print '<tr>';
		print '<td>';
		print $payline->cc_type;
		print '</td>';
		print '<td>';
		print "{$payline->cc_name}";
		print '</td>';
		print "<td>{$payline->cc_number}</td>";
		print "<td>{$payline->approval_code}</td>";
		print '<td align="right">$'.number_format($payline->amount,2)."</td>";
		print '</tr>';
	}
	$previousrow = $payline;
	foreach ($group as $field) {
		$total[$field] += $payline->amount;
		$count[$field] ++;
	}
	$total['grand'] += $payline->amount;
	$count['grand'] ++;

}
foreach ($group as $field) {
	//We have a final totals
	print '<tr>';
	print '<td colspan="4" align="right">';
	print "Total of {$count[$field]} {$payline->$field}";
	print '</td>';
	print '<td align="right">$';
	print number_format ($total[$field],2);
	print '</td>';
	print '</tr>';
}
print '<tr>';
print '<td colspan="4" align="right">Total of '. $count['grand'] . ' charges.';
print '</td>';
print '<td align="right">$';
print number_format($total['grand'],2);
print '</td>';
print '</tr>';
?>
</table>
