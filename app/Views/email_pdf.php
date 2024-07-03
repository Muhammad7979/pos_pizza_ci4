<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            text-align: center;
        }

        table {
            width: 80%;
             margin: 0px auto; /* Center the table and add some space around it */
            border-collapse: collapse;
             border: 1px solid #ddd; /* Add a border to the table */
        }

        th, td {
            padding: 10px; /* Add padding to table header and data cells */
            border: 1px solid #ddd; /* Add a border to all cells */
        }

        th {
            background-color: #f2f2f2; /* Add a background color to table headers */
        }
    </style>
</head>
<body>
    <h1>All Items</h1>
    <table>
    <thead>
        <tr>
            <th>Sr No</th>
            <th>Items Name</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $i = 1;
        foreach( $pdf_content as $item){ ?>
        <tr>
            <td><?php echo $i ?></td>
            <td><?php echo $item['item_name'] ?></td>
            <td><?php echo $item['quantity']?></td>
        </tr>
        
        <?php 
          $i++;
          } 
         ?>
    
        <!-- Add more rows as needed -->
    </tbody>
</table>
</body>
</html>