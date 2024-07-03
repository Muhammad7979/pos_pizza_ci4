<?php $this->load->view("partial/header"); ?>
    <div id="title_bar" class="btn-toolbar print_hide">
        <div class="inner_block">
            <div id="page_title">
                <?php echo $title ?>
                <a href="<?php echo base_url("index.php/".$controller_name ."/" .service('router')->methodName()) ; ?>"
                   class="back_link" >&lt; Back</a>

                <a href="<?php echo base_url("index.php/".$controller_name) ; ?>"
                   class="back_link" >View another Report</a>
            </div>

        </div>
    </div>

    <div id="receipt_header" class="normal_hide">
        <?php
        if ($this->Appconfig->get('company_logo') != '') {
            ?>
            <div id="company_name"><img id="image"
                                        src="<?php echo base_url('uploads/' . $this->Appconfig->get('company_logo')); ?>"
                                        alt="company_logo"/></div>
            <?php
        }
        ?>

        <!--        <div id="company_name">--><?php //echo $this->config->item('company'); ?><!--</div>-->

        <?php
        $branch_code = $this->gu->getStoreBranchCode();
        $store = $this->gu->getStoreInfoByBranchCode($branch_code);

        $address = $store['address'];
        $phone = $store['phone'];

        ?>

        <div id="company_address"><?php echo $address; ?></div>
        <div id="company_phone"><?php echo $phone; ?></div>

        <div><?php echo $subtitle ?></div>
    </div>

    <div class="inner_block" style="margin-top: 20px;">

        <div id="page_subtitle" class="print_hide"><?php echo $subtitle ?></div>

        <div id="table_holder">
            <table id="table" style="border: 1px solid black;" class="table table-bordered"></table>
        </div>

        <div id="report_summary">
            <?php
            foreach ($summary_data as $name => $value) {
                ?>
                <div
                    class="summary_row"><?php echo $this->lang->line('reports_' . $name) . ': ' . to_currency($value); ?></div>
                <?php
            }
            ?>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            <?php $this->load->view('partial/bootstrap_tables_locale'); ?>

            $('#table').bootstrapTable({
                columns: <?php echo transform_headers_readonly($headers); ?>,
                pageSize: <?php echo $this->config->item('lines_per_page')+10; ?>,
                striped: true,
                sortable: true,
                showExport: true,
                pagination: true,
                showColumns: true,
                showExport: true,
                data: <?php echo json_encode($data); ?>,
                iconSize: 'sm',
                paginationVAlign: 'bottom',
                escape: false,
                onLoadSuccess: function(){
                    $('.columns-right').addClass('print_hide');
                    $('.page-list').addClass('print_hide');
                }
            });

            $('.columns-right').addClass('print_hide');
            $('.page-list').addClass('print_hide');


        });
    </script>

<?php $this->load->view("partial/footer"); ?>