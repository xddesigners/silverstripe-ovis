<dl>
    <% if $Name %>
        <dt><strong>Name</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Name</dd>
    <% end_if %>
    <% if $Email %>
        <dt><strong>Email</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Email</dd>
    <% end_if %>
    <% if $Phone %>
        <dt><strong>Phone</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Phone</dd>
    <% end_if %>
    <% if $Presentation %>
        <dt><strong>About</strong></dt>
        <dd style="margin: 4px 0 14px 0">
            <a href="$Presentation.Link">$Presentation.Title ($Presentation.PriceNice)</a>
        </dd>
    <% end_if %>

    <% if $TradeIn %>
        <dt><strong>TradeIn</strong></dt>
        <dd style="margin: 4px 0 14px 0">$TradeIn</dd>
    <% end_if %>
    <% if $Brand %>
        <dt><strong>Brand</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Brand</dd>
    <% end_if %>
    <% if $Model %>
        <dt><strong>Model</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Model</dd>
    <% end_if %>
    <% if $ConstructionYear %>
        <dt><strong>ConstructionYear</strong></dt>
        <dd style="margin: 4px 0 14px 0">$ConstructionYear</dd>
    <% end_if %>
    <% if $Condition %>
        <dt><strong>Condition</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Condition</dd>
    <% end_if %>
    <% if $Undamaged %>
        <dt><strong>Undamaged</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Undamaged</dd>
    <% end_if %>
    <% if $Upholstery %>
        <dt><strong>Upholstery</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Upholstery</dd>
    <% end_if %>
    <% if $Tires %>
        <dt><strong>Tires</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Tires</dd>
    <% end_if %>
</dl>

<% if $Question %>
    <p>$Question</p>
<% end_if %>
