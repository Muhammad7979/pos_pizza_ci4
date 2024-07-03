<?php include(APPPATH . 'Views/partial/header.php'); ?>
<div id="title_bar" class="btn-toolbar print_hide">
        <div class="inner_block">
            <div id="page_title">
                <?php echo $title ?>
                <a href="<?php echo base_url("index.php/".$this->router->fetch_class() ."/" .$this->router->fetch_method()) ; ?>"
                   class="back_link" >&lt; Back</a>

                <a href="<?php echo base_url("index.php/".$this->router->fetch_class()) ; ?>"
                   class="back_link" >View another Report</a>
            </div>
        </div>
    </div>

    <div class="inner_block" style="margin-top: 20px;">

        <div id="page_subtitle"><?php echo $subtitle ?></div>

        <div class="ct-chart ct-golden-section" id="chart1"></div>

        <?php $this->load->view($chart_type); ?>

        <div id="chart_report_summary">
            <?php
            foreach ($summary_data_1 as $name => $value) {
                ?>
                <div
                    class="summary_row"><?php echo $this->lang->line('reports_' . $name) . ': ' . to_currency($value); ?></div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php include(APPPATH . 'Views/partial/footer.php'); ?>