<form method="post" action="{$WEB_ROOT}/index.php?m=reversedns&serviceid={$serviceid}">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Reverse DNS Manage</h3>
        </div>
        <div class="panel-body">
{if $success}
    <div class="alert alert-success">
        {$success}
    </div>
{/if}
{if $error}
    <div class="alert alert-danger">
        {$error}
    </div>
{/if}
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Available Reverse DNS</th>
                        <th>New Reverse DNS</th>
                        <th>Process</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$ipaddresses item=ip}
                    <tr>
                        <td>{$ip.address}</td>
                        <td>{$ip.current_rdns}</td>
                        <td>
                            <input type="text" name="new_rdns[{$ip.address}]" value="{$ip.current_rdns}" class="form-control" />

                        </td>
                        <td>
                            <button type="submit" name="update_rdns" value="{$ip.address}" class="btn btn-primary">Update</button>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</form>
