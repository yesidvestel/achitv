<style>
.contenedor{
    position: relative;
    display: inline-block;
    text-align: center;
	width: 80%;	
    top: 10%;
    left: 10%;
}
.texto-encima{
    position: absolute;
    top: 10px;
    left: 10px;
}
.centrado{
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
	color: aliceblue;
	font-size: 30px;
	font-family: impact;
}
.centrado2{
    position: absolute;
    top: 60%;
    left: 60%;
    transform: translate(-50%, -50%);
	color: aliceblue;
	font-size: 20px;
	font-family: impact;
}

</style>
<article class="content">
    <div class="card card-block">
        <div id="notify" class="alert alert-success" style="display:none;">
            <a href="#" class="close" data-dismiss="alert">&times;</a>

            <div class="message"></div>
        </div>
        <div class="grid_3 grid_4">
            <div class="header-block">
                <h3 class="title">
                    <?php echo $this->lang->line('') ?>Lista de Naps
                        
                </h3></div>
			<?php foreach ($naps as $row) { 
					$cid = $row['nat']; ?>
			<a href="#" class="open_modal"  data-nat="<?=$row['nat']?>">
			<div class="col-xl-2 col-md-4 col-xs-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
										
                                        <img alt="image" id="dpic" class="img-responsive contenedor"
                                     src="<?php echo base_url('userfiles/attach/nap.png') ?>">
										<h5 style="position: absolute" class="centrado">Nº <?php echo $cid ?></h5>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				<?php }; ?>
           </a>
        </div>
    </div>
</article>
<div id="delete_model" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo $this->lang->line('Delete') ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo $this->lang->line('delete this document') ?></p>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="object-id" value="">
                <input type="hidden" id="action-url" value="tools/delete_document">
                <button type="button" data-dismiss="modal" class="btn btn-primary"
                        id="delete-confirm"><?php echo $this->lang->line('Delete') ?></button>
                <button type="button" data-dismiss="modal"
                        class="btn"><?php echo $this->lang->line('Cancel') ?></button>
            </div>
        </div>
    </div>
</div>
<div id="pop_model" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Disponibilidad de puertos</h4>
            </div>			
            <div class="modal-body">
                <form id="form_model">
					<div class="form-group row">						
                    
                        <div>
							
							<table align="center" width="100%">
							<tr>
							<td colspan="16" align="center"><img alt="image" id="dpic" class="img-responsive"
                                     src="<?php echo base_url('userfiles/attach/napaabierta2.png') ?>"></td>	
							</tr>
							<tr>
								<?php for ($i=1;$i<=16;$i++){?>
								<td>
									<i class="icon-circle contenedor <?php if (isset($comprobacion[$i])){ echo 'pink';}else{ echo 'teal';} ?> font-large-1">
									<h5 style="position: absolute" class="centrado2"><?=$i?></h5></i>
								</td>
								<?php } ?>
							</tr>
							</table>
							
                    	
                        </div>
                    
						
				</div>
                </div>
					
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
	
	$(document).on("click",'.open_modal',function(e){
		e.preventDefault();
		var nat =$(this).data("nat");
		
		$("#pop_model").modal("show");
	});
    $(document).ready(function () {

        $('#doctable').DataTable({

            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "<?php echo site_url('tools/document_load_list')?>",
                "type": "POST"
            },
            "columnDefs": [
                {
                    "targets": [0],
                    "orderable": false,
                },
            ],

        });

    });
</script>
