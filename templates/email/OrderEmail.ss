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
    <% if $Address %>
        <dt><strong>Address</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Address</dd>
    <% end_if %>
    <% if $PostalCode %>
        <dt><strong>PostalCode</strong></dt>
        <dd style="margin: 4px 0 14px 0">$PostalCode</dd>
    <% end_if %>
    <% if $Locality %>
        <dt><strong>Locality</strong></dt>
        <dd style="margin: 4px 0 14px 0">$Locality</dd>
    <% end_if %>
    <% if $Presentation %>
        <dt><strong>About</strong></dt>
        <dd style="margin: 4px 0 14px 0">
            <a href="$Presentation.Link">$Presentation.Title ($Presentation.PriceNice)</a>
        </dd>
    <% end_if %>
</dl>

<% if $Question %>
    <p>Question</p>
<% end_if %>