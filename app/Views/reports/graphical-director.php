<?php  include(APPPATH . 'Views/partial/header.php'); ?>
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
    <style type="text/css">
        .ct-bar {
            stroke-width: 20px !important;
        }
        .ct-series-a .ct-bar{
            stroke: #3498db;
        }
        text.ct-label{
            /*writing-mode: vertical-rl;*/
            font-weight: 600;
        }
        .nav{
            display: flex;
            background: lightgrey;
        }
        .nav-fill .nav-item {
            -ms-flex: 1 1 auto;
            flex: 1 1 auto;
            font-weight: 600;
            font-size: 1.5rem;
            text-align: center;
        }
        .nav-fill a{
            color: black;
            border-radius: 0px !important;
        }
        #chart_report_summary {
            font-weight: bold;
            font-size: 16px;
        }
    </style>
    <div class="inner_block" style="margin-top: 20px;">

        <div id="page_subtitle"><?php echo $subtitle ?></div>

        <div class="container">
            <ul class="nav nav-pills nav-fill navtop print_hide">
                <li class="nav-item active">
                    <a class="nav-link active" href="#sales" data-toggle="tab" id="sale-element">SALES</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#quantities" data-toggle="tab" id="qty-element">QUANTITIES</a>
                </li>
            </ul>
            <div class="tab-content float-right">
                <div class="tab-pane active" role="tabpanel" id="sales">
                    <div class="ct-chart ct-golden-section" id="chart1"></div>
                </div>
                <div class="tab-pane" role="tabpanel" id="quantities">
                    <div class="ct-chart ct-golden-section" id="chart2"></div>
                </div>
            </div>
        </div>

        

        <?php return view($chart_type.'-sales'); ?>
        <?php return view($chart_type.'-qty'); ?>

        <div id="chart_report_summary">
            <?php
            foreach ($summary_data_1 as $name => $value) {
                ?>
                <div
                    class="summary_row"><?php echo lang('reports_lang.reports_' . $name) . ': ' . to_currency($value); ?></div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php  include(APPPATH . 'Views/partial/header.php'); ?>
