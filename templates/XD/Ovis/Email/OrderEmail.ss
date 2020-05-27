<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
    <title><%t SilverShop\ShopEmail.ConfirmationTitle "Order Confirmation" %></title>

    <style type="text/css">
        *{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
        }
        table.order-table{
            border-collapse: collapse;
            border: 1px solid #000;
            width: 100%;
        }
        table.order-table tr td{
            border: 1px solid #000;
            padding: 10px 10px;
        }
        table.order-table tr td.label{
            width: 200px;
            font-weight: bold;
        }

    </style>
</head>
<body>

<p><%t XD\Ovis\Control\OvisPageController.OrderPlaced 'Order placed at: ' %> $Created </p>


<table class="order-table">
    <% if $Name %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Name 'Name' %></td>
            <td class="value">$Name</td>
        </tr>
    <% end_if %>
    <% if $Email %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Email 'Email' %></td>
            <td class="value">$Email</td>
        </tr>
    <% end_if %>
    <% if $Phone %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Phone 'Phone' %></td>
            <td class="value">$Phone</td>
        </tr>
    <% end_if %>

    <% if $Presentation %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Presentation 'Presentation' %></td>
            <td class="value">
                <a href="$Presentation.Link">$Presentation.Title ($Presentation.PriceNice)</a>
            </td>
        </tr>
    <% end_if %>

    <% if $CaravanModel %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.CaravanModel 'Caravanmodel' %></td>
            <td class="value">
                <a href="$CaravanModel.Link">$CaravanModel.Title ($CaravanModel.PriceCurrency)</a>
            </td>
        </tr>
    <% end_if %>

    <% if $TradeIn %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.TradeIn 'TradeIn' %></td>
            <td class="value">$TradeIn</td>
        </tr>
    <% end_if %>
    <% if $Brand %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Brand 'Brand' %></td>
            <td class="value">$Brand</td>
        </tr>
    <% end_if %>
    <% if $Model %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Model 'Model' %></td>
            <td class="value">$Model</td>
        </tr>
    <% end_if %>
    <% if $ConstructionYear %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.ConstructionYear 'ConstructionYear' %></td>
            <td class="value">$ConstructionYear</td>
        </tr>
    <% end_if %>
    <% if $Condition %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Condition 'Condition' %></td>
            <td class="value">$ConditionNice</td>
        </tr>
    <% end_if %>
    <% if $Undamaged %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Undamaged 'Undamaged' %></td>
            <td class="value">$UndamagedNice</td>
        </tr>
    <% end_if %>
    <% if $Upholstery %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Upholstery 'Upholstery' %></td>
            <td class="value">$UpholsteryNice</td>
        </tr>
    <% end_if %>
    <% if $Tires %>
        <tr>
            <td class="label"><%t XD\Ovis\Control\OvisPageController.Tires 'Tires' %></td>
            <td class="value">$TiresNice</td>
        </tr>
    <% end_if %>

    <% if $Question %>
        <tr>
            <td class="label">
                <%t XD\Ovis\Control\OvisPageController.Comments 'Commments' %>
            </td>
            <td class="value">
                <p>$Question</p>
            </td>
        </tr>
    <% end_if %>

</table>

</body>
</html>
