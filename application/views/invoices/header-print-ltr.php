<table>
        <tr>
            
            <td>

            </td>
            <td class="myw">
			<table class="top_sum">
                 <tr>
                       <td colspan="1" class="t_center"><h2 ><strong><?php echo $this->config->item('ctitle') ?></strong></h2><br></td>
                    </tr>
			<tr>
         <td class="t_center"><strong><?php echo $this->config->item('address') ?></strong></td>
			</tr>
			<tr>
            <td class="t_center"><strong>Nit: <?php echo $this->config->item('postbox') ?></strong></td>
			</tr>
			<!--<tr>
            <td class="t_center"><?php //echo $this->config->item('phone') ?></td>
			</tr> -->
			<?php if($invoice['refer']) { ?>
			<tr>
            <td class="t_center"><strong><?php echo $this->lang->line('') .'Sede: '. $invoice['refer'] ?></strong></td>
			</tr>
			<?php $fecha=date("d/m/Y").' '.date("g:i a"); 
			if(isset($transaccion)){
				$fecha=$transaccion->date;
			}
			?>

			<tr>
				<td class="t_center"><strong><?php echo $fecha ?></strong></td>
			</tr>
			<?php } ?>
			</table>



            </td>
        </tr>
    </table><br>