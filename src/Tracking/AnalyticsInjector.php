<?php

namespace Concrete\Package\LassoCrm\Tracking;

use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;

class AnalyticsInjector
{
    /** @var ConnectionConfig */
    private $connectionConfig;

    public function __construct(ConnectionConfig $connectionConfig)
    {
        $this->connectionConfig = $connectionConfig;
    }

    public function getScriptHtml(?string $accountIdOverride = null): string
    {
        $accountId = trim((string) $accountIdOverride);
        if ($accountId === '') {
            $accountId = $this->connectionConfig->getTrackingAccountId();
        }

        if ($accountId === '') {
            return '';
        }

        $accountIdJs = json_encode($accountId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        return <<<HTML
<script>
window.LassoAnalyticsAPI = 2;
(function(w,d,s,u,n){
  w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)};
  var a=d.createElement(s),m=d.getElementsByTagName(s)[0];
  a.async=1;a.src=u;m.parentNode.insertBefore(a,m);
})(window,document,'script','https://platform.lassocrm.com/wt/analytics.min.js','LassoAnalytics');
LassoAnalytics('setAccountId', {$accountIdJs});
LassoAnalytics('pageView');
LassoAnalytics('patchRegistrationForms');
</script>
HTML;
    }

    public function shouldInjectGlobally(): bool
    {
        return $this->connectionConfig->isGlobalTrackingEnabled()
            && $this->connectionConfig->getTrackingAccountId() !== '';
    }
}
