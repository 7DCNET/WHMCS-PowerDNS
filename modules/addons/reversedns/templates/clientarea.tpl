
<form method="post" action="{$WEB_ROOT}/index.php?m=reversedns&serviceid={$serviceid}">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Reverse DNS Yönetimi</h3>
        </div>
        <div class="panel-body">
{if $success}
    <div class="alert alert-success">
        {$success}
    </div>
    <script type="text/javascript">
        setTimeout(function() {
            window.location.href = "index.php?m=reversedns";
        }, 4000); // 4 saniye sonra yönlendir
    </script>
{/if}
{if $error}
    <div class="alert alert-danger">
        {$error}
    </div>
{/if}
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>IP Adresi</th>
                        <th>Mevcut Reverse DNS</th>
                        <th>Yeni Reverse DNS</th>
                        <th>İşlem</th>
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
                            <button type="submit" name="update_rdns" value="{$ip.address}" class="btn btn-primary">Güncelle</button>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</form>
