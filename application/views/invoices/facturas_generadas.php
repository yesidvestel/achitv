<article class="content">
    <div class="card card-block">
        <div id="notify" class="alert alert-success" style="display:none;">
            <a href="#" class="close" data-dismiss="alert">&times;</a>

            <div class="message"></div>
        </div>
        <div class="grid_3 grid_4">
            <h6><?php echo $this->lang->line('') ?>Facturas Generadas</h6>
             <hr>
             &nbsp;&nbsp;&nbsp;&nbsp;<a class="btn btn-danger" href="<?=base_url().'invoices/generar_pdf_facturas_generadas?fecha='.$fecha.'&pay_acc='.$pay_acc ?>">Exportar a PDF <img width="20px" src="<?=base_url()?>assets/images/icons/pdf.png"></a> &nbsp;<a class="btn btn-success" href="<?=base_url().'invoices/generar_excel_facturas_generadas?fecha='.$fecha.'&pay_acc='.$pay_acc ?>">Exportar a Excel<img width="20px" src="<?=base_url()?>assets/images/icons/excel.png"></a>
             <hr>

            <div class="table-responsive">
                <table id="clientstable" class="table-striped" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                       <th>#</th>
                        <th><?php echo $this->lang->line('Name') ?></th>
                        <th>Celular</th>
                        <th>Cedula</th>
                        
                        <th><?php echo $this->lang->line('Settings') ?></th>


                    </tr>
                    </thead>
                    <tbody>
                      
                    </tbody>

                    <tfoot>
                    <tr>
                       
                        <th>#</th>
                        <th><?php echo $this->lang->line('Name') ?></th>
                        <th>Celular</th>
                        <th>Cedula</th>
                       
                        <th><?php echo $this->lang->line('Settings') ?></th>


                    </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</article>
<script type="text/javascript">
    $(document).ready(function () {
        $('#clientstable').DataTable({
            'processing': true,
            'serverSide': true,
            'stateSave': true,
            'order': [],
            'ajax': {
                'url': "<?php echo site_url('invoices/lista_facturas_generadas?fecha='.$fecha.'&pay_acc='.$pay_acc)?>",
                'type': 'POST'
            },
            'columnDefs': [
                {
                    'targets': [0],
                    'orderable': false,
                },
            ],
            
        });
    });
</script>

