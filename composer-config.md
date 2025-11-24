<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">

        <title>Config - Composer</title>
        <meta name="description" content="A Dependency Manager for PHP">
        <meta name="viewport" content="width=device-width,initial-scale=1">

        <link rel="stylesheet" href="/build/app.css?v=2">
    </head>

    <body>
        <div id="container">
            <header>
                                    <a href="/"
                       title="Go back to the homepage"
                       aria-label="Go back to the homepage"><img src="/img/logo-composer-transparent.png" style="width:30px; margin-right: 10px" />Home</a><a class=""
                       href="/doc/00-intro.md"
                       title="Getting started with Composer"
                       aria-label="Getting started with Composer">Getting Started</a><a class=""
                       href="/download/"
                       title="Go the the Download page to see how to download Composer"
                       aria-label="Go the the Download page to see how to download Composer">Download</a><a class="active"
                       href="/doc/"
                       title="View the Composer documentation"
                       aria-label="View the Composer documentation">Documentation</a><a class="last"
                       href="https://packagist.org/"
                       title="Browse Composer packages on packagist.org (external link to Packagist.org)"
                       aria-label="Browse Composer packages on packagist.org (external link to Packagist.org)">Browse Packages</a>            </header>
            <main role="main">
                <div id="main">
                        <div id="searchbar" class="clearfix">
        <div id="docsearch"></div>
    </div>

            <ul class="toc">
                
                                                            <li>
                <a href="#process-timeout">process-timeout</a> 
                                    <ul>
                            
                                                            <li>
                <a href="#disabling-timeouts-for-an-individual-script-command">Disabling timeouts for an individual script command</a> 
                            </li>
            
                    </ul>
                            </li>
                                <li>
                <a href="#allow-plugins">allow-plugins</a> 
                            </li>
                                <li>
                <a href="#use-include-path">use-include-path</a> 
                            </li>
                                <li>
                <a href="#preferred-install">preferred-install</a> 
                            </li>
                                <li>
                <a href="#audit">audit</a> 
                                    <ul>
                            
                                                            <li>
                <a href="#ignore">ignore</a> 
                            </li>
                                <li>
                <a href="#abandoned">abandoned</a> 
                            </li>
                                <li>
                <a href="#ignore-abandoned">ignore-abandoned</a> 
                            </li>
                                <li>
                <a href="#ignore-severity">ignore-severity</a> 
                            </li>
                                <li>
                <a href="#ignore-unreachable">ignore-unreachable</a> 
                            </li>
                                <li>
                <a href="#block-insecure">block-insecure</a> 
                            </li>
                                <li>
                <a href="#block-abandoned">block-abandoned</a> 
                            </li>
            
                    </ul>
                            </li>
                                <li>
                <a href="#use-parent-dir">use-parent-dir</a> 
                            </li>
                                <li>
                <a href="#store-auths">store-auths</a> 
                            </li>
                                <li>
                <a href="#github-protocols">github-protocols</a> 
                            </li>
                                <li>
                <a href="#github-oauth">github-oauth</a> 
                            </li>
                                <li>
                <a href="#gitlab-domains">gitlab-domains</a> 
                            </li>
                                <li>
                <a href="#gitlab-oauth">gitlab-oauth</a> 
                            </li>
                                <li>
                <a href="#gitlab-token">gitlab-token</a> 
                            </li>
                                <li>
                <a href="#gitlab-protocol">gitlab-protocol</a> 
                            </li>
                                <li>
                <a href="#forgejo-domains">forgejo-domains</a> 
                            </li>
                                <li>
                <a href="#forgejo-token">forgejo-token</a> 
                            </li>
                                <li>
                <a href="#disable-tls">disable-tls</a> 
                            </li>
                                <li>
                <a href="#secure-http">secure-http</a> 
                            </li>
                                <li>
                <a href="#bitbucket-oauth">bitbucket-oauth</a> 
                            </li>
                                <li>
                <a href="#cafile">cafile</a> 
                            </li>
                                <li>
                <a href="#capath">capath</a> 
                            </li>
                                <li>
                <a href="#http-basic">http-basic</a> 
                            </li>
                                <li>
                <a href="#bearer">bearer</a> 
                            </li>
                                <li>
                <a href="#platform">platform</a> 
                            </li>
                                <li>
                <a href="#vendor-dir">vendor-dir</a> 
                            </li>
                                <li>
                <a href="#bin-dir">bin-dir</a> 
                            </li>
                                <li>
                <a href="#data-dir">data-dir</a> 
                            </li>
                                <li>
                <a href="#cache-dir">cache-dir</a> 
                            </li>
                                <li>
                <a href="#cache-files-dir">cache-files-dir</a> 
                            </li>
                                <li>
                <a href="#cache-repo-dir">cache-repo-dir</a> 
                            </li>
                                <li>
                <a href="#cache-vcs-dir">cache-vcs-dir</a> 
                            </li>
                                <li>
                <a href="#cache-files-ttl">cache-files-ttl</a> 
                            </li>
                                <li>
                <a href="#cache-files-maxsize">cache-files-maxsize</a> 
                            </li>
                                <li>
                <a href="#cache-read-only">cache-read-only</a> 
                            </li>
                                <li>
                <a href="#bin-compat">bin-compat</a> 
                            </li>
                                <li>
                <a href="#prepend-autoloader">prepend-autoloader</a> 
                            </li>
                                <li>
                <a href="#autoloader-suffix">autoloader-suffix</a> 
                            </li>
                                <li>
                <a href="#optimize-autoloader">optimize-autoloader</a> 
                            </li>
                                <li>
                <a href="#sort-packages">sort-packages</a> 
                            </li>
                                <li>
                <a href="#classmap-authoritative">classmap-authoritative</a> 
                            </li>
                                <li>
                <a href="#apcu-autoloader">apcu-autoloader</a> 
                            </li>
                                <li>
                <a href="#github-domains">github-domains</a> 
                            </li>
                                <li>
                <a href="#github-expose-hostname">github-expose-hostname</a> 
                            </li>
                                <li>
                <a href="#use-github-api">use-github-api</a> 
                            </li>
                                <li>
                <a href="#notify-on-install">notify-on-install</a> 
                            </li>
                                <li>
                <a href="#discard-changes">discard-changes</a> 
                            </li>
                                <li>
                <a href="#archive-format">archive-format</a> 
                            </li>
                                <li>
                <a href="#archive-dir">archive-dir</a> 
                            </li>
                                <li>
                <a href="#htaccess-protect">htaccess-protect</a> 
                            </li>
                                <li>
                <a href="#lock">lock</a> 
                            </li>
                                <li>
                <a href="#platform-check">platform-check</a> 
                            </li>
                                <li>
                <a href="#secure-svn-domains">secure-svn-domains</a> 
                            </li>
                                <li>
                <a href="#bump-after-update">bump-after-update</a> 
                            </li>
                                <li>
                <a href="#allow-missing-requirements">allow-missing-requirements</a> 
                            </li>
                                <li>
                <a href="#update-with-minimal-changes">update-with-minimal-changes</a> 
                            </li>
            
        </ul>
    
    <h1 id="config">Config<a href="#config" class="anchor">#</a></h1>
<p>This chapter will describe the <code>config</code> section of the <code>composer.json</code>
<a href="04-schema.md">schema</a>.</p>
<h2 id="process-timeout">process-timeout<a href="#process-timeout" class="anchor">#</a></h2>
<p>The timeout in seconds for process executions, defaults to 300 (5mins).
The duration processes like <code>git clone</code>s can run before
Composer assumes they died out. You may need to make this higher if you have a
slow connection or huge vendors.</p>
<p>Example:</p>
<pre><code class="language-javascript">{
    "config": {
        "process-timeout": 900
    }
}</code></pre>
<h3 id="disabling-timeouts-for-an-individual-script-command">Disabling timeouts for an individual script command<a href="#disabling-timeouts-for-an-individual-script-command" class="anchor">#</a></h3>
<p>To disable the process timeout on a custom command under <code>scripts</code>, a static
helper is available:</p>
<pre><code class="language-javascript">{
    "scripts": {
        "test": [
            "Composer\\Config::disableProcessTimeout",
            "phpunit"
        ]
    }
}</code></pre>
<h2 id="allow-plugins">allow-plugins<a href="#allow-plugins" class="anchor">#</a></h2>
<p>Defaults to <code>{}</code> which does not allow any plugins to be loaded.</p>
<p>As of Composer 2.2.0, the <code>allow-plugins</code> option adds a layer of security
allowing you to restrict which Composer plugins are able to execute code during
a Composer run.</p>
<p>When a new plugin is first activated, which is not yet listed in the config option,
Composer will print a warning. If you run Composer interactively it will
prompt you to decide if you want to execute the plugin or not.</p>
<p>Use this setting to allow only packages you trust to execute code. Set it to
an object with package name patterns as keys. The values are <strong>true</strong> to allow
and <strong>false</strong> to disallow while suppressing further warnings and prompts.</p>
<pre><code class="language-javascript">{
    "config": {
        "allow-plugins": {
            "third-party/required-plugin": true,
            "my-organization/*": true,
            "unnecessary/plugin": false
        }
    }
}</code></pre>
<p>You can also set the config option itself to <code>false</code> to disallow all plugins, or <code>true</code> to allow all plugins to run (NOT recommended). For example:</p>
<pre><code class="language-javascript">{
    "config": {
        "allow-plugins": false
    }
}</code></pre>
<h2 id="use-include-path">use-include-path<a href="#use-include-path" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If <code>true</code>, the Composer autoloader will also look for classes
in the PHP include path.</p>
<h2 id="preferred-install">preferred-install<a href="#preferred-install" class="anchor">#</a></h2>
<p>Defaults to <code>dist</code> and can be any of <code>source</code>, <code>dist</code> or <code>auto</code>. This option
allows you to set the install method Composer will prefer to use. Can
optionally be an object with package name patterns for keys for more granular install preferences.</p>
<pre><code class="language-javascript">{
    "config": {
        "preferred-install": {
            "my-organization/stable-package": "dist",
            "my-organization/*": "source",
            "partner-organization/*": "auto",
            "*": "dist"
        }
    }
}</code></pre>
<ul>
<li><code>source</code> means Composer will install packages from their <code>source</code> if there
is one. This is typically a git clone or equivalent checkout of the version
control system the package uses. This is useful if you want to make a bugfix
to a project and get a local git clone of the dependency directly.</li>
<li><code>auto</code> is the legacy behavior where Composer uses <code>source</code> automatically
for dev versions, and <code>dist</code> otherwise.</li>
<li><code>dist</code> (the default as of Composer 2.1) means Composer installs from <code>dist</code>,
where possible. This is typically a zip file download, which is faster than
cloning the entire repository.</li>
</ul>
<blockquote>
<p><strong>Note:</strong> Order matters. More specific patterns should be earlier than
more relaxed patterns. When mixing the string notation with the hash
configuration in global and package configurations the string notation
is translated to a <code>*</code> package pattern.</p>
</blockquote>
<h2 id="audit">audit<a href="#audit" class="anchor">#</a></h2>
<p>Security audit configuration options</p>
<h3 id="ignore">ignore<a href="#ignore" class="anchor">#</a></h3>
<p>A list of advisory ids, remote ids or CVE ids that are reported but let the audit command pass.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore": {
                "CVE-1234": "The affected component is not in use.",
                "GHSA-xx": "The security fix was applied as a patch.",
                "PKSA-yy": "Due to mitigations in place the update can be delayed."
            }
        }
    }
}</code></pre>
<p>or</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore": ["CVE-1234", "GHSA-xx", "PKSA-yy"]
        }
    }
}</code></pre>
<h3 id="abandoned">abandoned<a href="#abandoned" class="anchor">#</a></h3>
<p>Defaults to <code>report</code> in Composer 2.6, and defaults to <code>fail</code> from Composer 2.7 on. Defines whether the audit command reports abandoned packages or not, this has three possible values:</p>
<ul>
<li><code>ignore</code> means the audit command does not consider abandoned packages at all.</li>
<li><code>report</code> means abandoned packages are reported as an error but do not cause the command to exit with a non-zero code.</li>
<li><code>fail</code> means abandoned packages will cause audits to fail with a non-zero code.</li>
</ul>
<p>Note, that this only applies to auditing, and not to the blocking of insecure
packages. To configure abandoned packages blocking, see the <a href="#block-abandoned"><code>block-abandoned</code></a>
option.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "abandoned": "report"
        }
    }
}</code></pre>
<p>Since Composer 2.7, the option can be overridden via the <a href="03-cli.md#composer-audit-abandoned"><code>COMPOSER_AUDIT_ABANDONED</code></a> environment variable.</p>
<p>Since Composer 2.8, the option can be overridden via the
<a href="03-cli.md#audit"><code>--abandoned</code></a> command line option, which overrides both the
config value and the environment variable.</p>
<h3 id="ignore-abandoned">ignore-abandoned<a href="#ignore-abandoned" class="anchor">#</a></h3>
<p>A list of abandoned package names that are reported but let the audit command pass.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore-abandoned": {
                "acme/*": "Work schedule for removal next month.",
                "acme/package": "The package is not in use"
            }
        }
    }
}</code></pre>
<p>or</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore-abandoned": ["acme/*", "acme/package"]
        }
    }
}</code></pre>
<h3 id="ignore-severity">ignore-severity<a href="#ignore-severity" class="anchor">#</a></h3>
<p>Defaults to <code>[]</code>. A list of severity levels that let the audit command pass even if there are security advisories
with the given severity.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore-severity": ["low"]
        }
    }
}</code></pre>
<h3 id="ignore-unreachable">ignore-unreachable<a href="#ignore-unreachable" class="anchor">#</a></h3>
<p>Defaults to <code>false</code>. Should unreachable repositories be ignored during a <code>composer audit</code>. This can be helpful if you are running the command
in an environment from which not all repositories can be accessed.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "ignore-unreachable": true
        }
    }
}</code></pre>
<h3 id="block-insecure">block-insecure<a href="#block-insecure" class="anchor">#</a></h3>
<p>Defaults to <code>true</code>. If <code>true</code>, any package versions affected by security advisories cannot be used
during a composer update/required/delete command unless the security advisories are ignored.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "block-insecure": false
        }
    }
}</code></pre>
<h3 id="block-abandoned">block-abandoned<a href="#block-abandoned" class="anchor">#</a></h3>
<p>Defaults to <code>false</code>. If <code>true</code>, any abandoned packages cannot be used during a composer update/required/delete command.</p>
<pre><code class="language-javascript">{
    "config": {
        "audit": {
            "block-abandoned": true
        }
    }
}</code></pre>
<h2 id="use-parent-dir">use-parent-dir<a href="#use-parent-dir" class="anchor">#</a></h2>
<p>When running Composer in a directory where there is no composer.json, if there
is one present in a directory above Composer will by default ask you whether
you want to use that directory's composer.json instead.</p>
<p>If you always want to answer yes to this prompt, you can set this config value
to <code>true</code>. To never be prompted, set it to <code>false</code>. The default is <code>"prompt"</code>.</p>
<blockquote>
<p><strong>Note:</strong> This config must be set in your global user-wide config for it
to work. Use for example <code>php composer.phar config --global use-parent-dir true</code>
to set it.</p>
</blockquote>
<h2 id="store-auths">store-auths<a href="#store-auths" class="anchor">#</a></h2>
<p>What to do after prompting for authentication, one of: <code>true</code> (always store),
<code>false</code> (do not store) and <code>"prompt"</code> (ask every time), defaults to <code>"prompt"</code>.</p>
<h2 id="github-protocols">github-protocols<a href="#github-protocols" class="anchor">#</a></h2>
<p>Defaults to <code>["https", "ssh", "git"]</code>. A list of protocols to use when cloning
from github.com, in priority order. By default <code>git</code> is present but only if <a href="#secure-http">secure-http</a>
is disabled, as the git protocol is not encrypted. If you want your origin remote
push URLs to be using https and not ssh (<code>git@github.com:...</code>), then set the protocol
list to be only <code>["https"]</code> and Composer will stop overwriting the push URL to an ssh
URL.</p>
<h2 id="github-oauth">github-oauth<a href="#github-oauth" class="anchor">#</a></h2>
<p>A list of domain names and oauth keys. For example using <code>{"github.com": "oauthtoken"}</code> as the value of this option will use <code>oauthtoken</code> to access
private repositories on github and to circumvent the low IP-based rate limiting
of their API. Composer may prompt for credentials when needed, but these can also be
manually set. Read more on how to get an OAuth token for GitHub and cli syntax
<a href="articles/authentication-for-private-packages.md#github-oauth">here</a>.</p>
<h2 id="gitlab-domains">gitlab-domains<a href="#gitlab-domains" class="anchor">#</a></h2>
<p>Defaults to <code>["gitlab.com"]</code>. A list of domains of GitLab servers.
This is used if you use the <code>gitlab</code> repository type.</p>
<h2 id="gitlab-oauth">gitlab-oauth<a href="#gitlab-oauth" class="anchor">#</a></h2>
<p>A list of domain names and oauth keys. For example using <code>{"gitlab.com": "oauthtoken"}</code> as the value of this option will use <code>oauthtoken</code> to access
private repositories on gitlab. Please note: If the package is not hosted at
gitlab.com the domain names must be also specified with the
<a href="06-config.md#gitlab-domains"><code>gitlab-domains</code></a> option.
Further info can also be found <a href="articles/authentication-for-private-packages.md#gitlab-oauth">here</a></p>
<h2 id="gitlab-token">gitlab-token<a href="#gitlab-token" class="anchor">#</a></h2>
<p>A list of domain names and private tokens. Private token can be either simple
string, or array with username and token. For example using <code>{"gitlab.com": "privatetoken"}</code> as the value of this option will use <code>privatetoken</code> to access
private repositories on gitlab. Using <code>{"gitlab.com": {"username": "gitlabuser", "token": "privatetoken"}}</code> will use both username and token for gitlab deploy
token functionality (<a href="https://docs.gitlab.com/ee/user/project/deploy_tokens/">https://docs.gitlab.com/ee/user/project/deploy_tokens/</a>)
Please note: If the package is not hosted at
gitlab.com the domain names must be also specified with the
<a href="06-config.md#gitlab-domains"><code>gitlab-domains</code></a> option. The token must have
<code>api</code> or <code>read_api</code> scope.
Further info can also be found <a href="articles/authentication-for-private-packages.md#gitlab-token">here</a></p>
<h2 id="gitlab-protocol">gitlab-protocol<a href="#gitlab-protocol" class="anchor">#</a></h2>
<p>A protocol to force use of when creating a repository URL for the <code>source</code>
value of the package metadata. One of <code>git</code> or <code>http</code>. (<code>https</code> is treated
as a synonym for <code>http</code>.) Helpful when working with projects referencing
private repositories which will later be cloned in GitLab CI jobs with a
<a href="https://docs.gitlab.com/ee/ci/variables/predefined_variables.html#predefined-variables-reference">GitLab CI_JOB_TOKEN</a>
using HTTP basic auth. By default, Composer will generate a git-over-SSH
URL for private repositories and HTTP(S) only for public.</p>
<h2 id="forgejo-domains">forgejo-domains<a href="#forgejo-domains" class="anchor">#</a></h2>
<p>Defaults to <code>["codeberg.org"]</code>. A list of domains of Forgejo servers.
This is used if you use the <code>forgejo</code> repository type.</p>
<h2 id="forgejo-token">forgejo-token<a href="#forgejo-token" class="anchor">#</a></h2>
<p>A list of domain names and username/access-tokens to authenticate against them. For
example using <code>{"codeberg.org": {"username": "forgejo-user", "token": "access-token"}}</code> as the
value of this option will let Composer authenticate against codeberg.org.
Please note: If the package is not hosted at
codeberg.org the domain names must be also specified with the
<a href="06-config.md#forgejo-domains"><code>forgejo-domains</code></a> option.
Further info can also be found <a href="articles/authentication-for-private-packages.md#forgejo-token">here</a></p>
<h2 id="disable-tls">disable-tls<a href="#disable-tls" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If set to true all HTTPS URLs will be tried with HTTP
instead and no network level encryption is performed. Enabling this is a
security risk and is NOT recommended. The better way is to enable the
php_openssl extension in php.ini. Enabling this will implicitly disable the
<code>secure-http</code> option.</p>
<h2 id="secure-http">secure-http<a href="#secure-http" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. If set to true only HTTPS URLs are allowed to be
downloaded via Composer. If you really absolutely need HTTP access to something
then you can disable it, but using <a href="https://letsencrypt.org/">Let's Encrypt</a> to
get a free SSL certificate is generally a better alternative.</p>
<h2 id="bitbucket-oauth">bitbucket-oauth<a href="#bitbucket-oauth" class="anchor">#</a></h2>
<p>A list of domain names and consumers. For example using <code>{"bitbucket.org": {"consumer-key": "myKey", "consumer-secret": "mySecret"}}</code>.
Read more <a href="articles/authentication-for-private-packages.md#bitbucket-oauth">here</a>.</p>
<h2 id="cafile">cafile<a href="#cafile" class="anchor">#</a></h2>
<p>Location of Certificate Authority file on local filesystem. In PHP 5.6+ you
should rather set this via openssl.cafile in php.ini, although PHP 5.6+ should
be able to detect your system CA file automatically.</p>
<h2 id="capath">capath<a href="#capath" class="anchor">#</a></h2>
<p>If cafile is not specified or if the certificate is not found there, the
directory pointed to by capath is searched for a suitable certificate.
capath must be a correctly hashed certificate directory.</p>
<h2 id="http-basic">http-basic<a href="#http-basic" class="anchor">#</a></h2>
<p>A list of domain names and username/passwords to authenticate against them. For
example using <code>{"example.org": {"username": "alice", "password": "foo"}}</code> as the
value of this option will let Composer authenticate against example.org.
More info can be found <a href="articles/authentication-for-private-packages.md#http-basic">here</a>.</p>
<h2 id="bearer">bearer<a href="#bearer" class="anchor">#</a></h2>
<p>A list of domain names and tokens to authenticate against them. For example using
<code>{"example.org": "foo"}</code> as the value of this option will let Composer authenticate
against example.org using an <code>Authorization: Bearer foo</code> header.</p>
<h2 id="platform">platform<a href="#platform" class="anchor">#</a></h2>
<p>Lets you fake platform packages (PHP and extensions) so that you can emulate a
production env or define your target platform in the config. Example: <code>{"php": "7.0.3", "ext-something": "4.0.3"}</code>.</p>
<p>This will make sure that no package requiring more than PHP 7.0.3 can be installed
regardless of the actual PHP version you run locally. However it also means
the dependencies are not checked correctly anymore, if you run PHP 5.6 it will
install fine as it assumes 7.0.3, but then it will fail at runtime. This also means if
<code>{"php":"7.4"}</code> is specified; no packages will be used that define <code>7.4.1</code> as minimum.</p>
<p>Therefore if you use this it is recommended, and safer, to also run the
<a href="03-cli.md#check-platform-reqs"><code>check-platform-reqs</code></a> command as part of your
deployment strategy.</p>
<p>If a dependency requires some extension that you do not have installed locally
you may ignore it instead by passing <code>--ignore-platform-req=ext-foo</code> to <code>update</code>,
<code>install</code> or <code>require</code>. In the long run though you should install required
extensions as if you ignore one now and a new package you add a month later also
requires it, you may introduce issues in production unknowingly.</p>
<p>If you have an extension installed locally but <em>not</em> on production, you may want
to artificially hide it from Composer using <code>{"ext-foo": false}</code>.</p>
<h2 id="vendor-dir">vendor-dir<a href="#vendor-dir" class="anchor">#</a></h2>
<p>Defaults to <code>vendor</code>. You can install dependencies into a different directory if
you want to. <code>$HOME</code> and <code>~</code> will be replaced by your home directory's path in
vendor-dir and all <code>*-dir</code> options below.</p>
<h2 id="bin-dir">bin-dir<a href="#bin-dir" class="anchor">#</a></h2>
<p>Defaults to <code>vendor/bin</code>. If a project includes binaries, they will be symlinked
into this directory.</p>
<h2 id="data-dir">data-dir<a href="#data-dir" class="anchor">#</a></h2>
<p>Defaults to <code>C:\Users\&lt;user&gt;\AppData\Roaming\Composer</code> on Windows,
<code>$XDG_DATA_HOME/composer</code> on unix systems that follow the XDG Base Directory
Specifications, and <code>$COMPOSER_HOME</code> on other unix systems. Right now it is only
used for storing past composer.phar files to be able to roll back to older
versions. See also <a href="03-cli.md#composer-home">COMPOSER_HOME</a>.</p>
<h2 id="cache-dir">cache-dir<a href="#cache-dir" class="anchor">#</a></h2>
<p>Defaults to <code>C:\Users\&lt;user&gt;\AppData\Local\Composer</code> on Windows,
<code>/Users/&lt;user&gt;/Library/Caches/composer</code> on macOS, <code>$XDG_CACHE_HOME/composer</code>
on unix systems that follow the XDG Base Directory Specifications, and
<code>$COMPOSER_HOME/cache</code> on other unix systems. Stores all the caches used by
Composer. See also <a href="03-cli.md#composer-home">COMPOSER_HOME</a>.</p>
<h2 id="cache-files-dir">cache-files-dir<a href="#cache-files-dir" class="anchor">#</a></h2>
<p>Defaults to <code>$cache-dir/files</code>. Stores the zip archives of packages.</p>
<h2 id="cache-repo-dir">cache-repo-dir<a href="#cache-repo-dir" class="anchor">#</a></h2>
<p>Defaults to <code>$cache-dir/repo</code>. Stores repository metadata for the <code>composer</code>
type and the VCS repos of type <code>svn</code>, <code>fossil</code>, <code>github</code> and <code>bitbucket</code>.</p>
<h2 id="cache-vcs-dir">cache-vcs-dir<a href="#cache-vcs-dir" class="anchor">#</a></h2>
<p>Defaults to <code>$cache-dir/vcs</code>. Stores VCS clones for loading VCS repository
metadata for the <code>git</code>/<code>hg</code> types and to speed up installs.</p>
<h2 id="cache-files-ttl">cache-files-ttl<a href="#cache-files-ttl" class="anchor">#</a></h2>
<p>Defaults to <code>15552000</code> (6 months). Composer caches all dist (zip, tar, ...)
packages that it downloads. Those are purged after six months of being unused by
default. This option allows you to tweak this duration (in seconds) or disable
it completely by setting it to 0.</p>
<h2 id="cache-files-maxsize">cache-files-maxsize<a href="#cache-files-maxsize" class="anchor">#</a></h2>
<p>Defaults to <code>300MiB</code>. Composer caches all dist (zip, tar, ...) packages that it
downloads. When the garbage collection is periodically ran, this is the maximum
size the cache will be able to use. Older (less used) files will be removed
first until the cache fits.</p>
<h2 id="cache-read-only">cache-read-only<a href="#cache-read-only" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. Whether to use the Composer cache in read-only mode.</p>
<h2 id="bin-compat">bin-compat<a href="#bin-compat" class="anchor">#</a></h2>
<p>Defaults to <code>auto</code>. Determines the compatibility of the binaries to be installed.
If it is <code>auto</code> then Composer only installs .bat proxy files when on Windows or WSL. If
set to <code>full</code> then both .bat files for Windows and scripts for Unix-based
operating systems will be installed for each binary. This is mainly useful if you
run Composer inside a linux VM but still want the <code>.bat</code> proxies available for use
in the Windows host OS. If set to <code>proxy</code> Composer will only create bash/Unix-style
proxy files and no .bat files even on Windows/WSL.</p>
<h2 id="prepend-autoloader">prepend-autoloader<a href="#prepend-autoloader" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. If <code>false</code>, the Composer autoloader will not be prepended to
existing autoloaders. This is sometimes required to fix interoperability issues
with other autoloaders.</p>
<h2 id="autoloader-suffix">autoloader-suffix<a href="#autoloader-suffix" class="anchor">#</a></h2>
<p>Defaults to <code>null</code>. When set to a non-empty string, this value will be used as a
suffix for the generated Composer autoloader. If set to <code>null</code>, the
<code>content-hash</code> value from the <code>composer.lock</code> file will be used if available;
otherwise, a random suffix will be generated.</p>
<h2 id="optimize-autoloader">optimize-autoloader<a href="#optimize-autoloader" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If <code>true</code>, always optimize when dumping the autoloader.</p>
<h2 id="sort-packages">sort-packages<a href="#sort-packages" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If <code>true</code>, the <code>require</code> command keeps packages sorted
by name in <code>composer.json</code> when adding a new package.</p>
<h2 id="classmap-authoritative">classmap-authoritative<a href="#classmap-authoritative" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If <code>true</code>, the Composer autoloader will only load classes
from the classmap. Implies <code>optimize-autoloader</code>.</p>
<h2 id="apcu-autoloader">apcu-autoloader<a href="#apcu-autoloader" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If <code>true</code>, the Composer autoloader will check for APCu and
use it to cache found/not-found classes when the extension is enabled.</p>
<h2 id="github-domains">github-domains<a href="#github-domains" class="anchor">#</a></h2>
<p>Defaults to <code>["github.com"]</code>. A list of domains to use in github mode. This is
used for GitHub Enterprise setups.</p>
<h2 id="github-expose-hostname">github-expose-hostname<a href="#github-expose-hostname" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. If <code>false</code>, the OAuth tokens created to access the
github API will have a date instead of the machine hostname.</p>
<h2 id="use-github-api">use-github-api<a href="#use-github-api" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>.  Similar to the <code>no-api</code> key on a specific repository,
setting <code>use-github-api</code> to <code>false</code> will define the global behavior for all
GitHub repositories to clone the repository as it would with any other git
repository instead of using the GitHub API. But unlike using the <code>git</code>
driver directly, Composer will still attempt to use GitHub's zip files.</p>
<h2 id="notify-on-install">notify-on-install<a href="#notify-on-install" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. Composer allows repositories to define a notification URL,
so that they get notified whenever a package from that repository is installed.
This option allows you to disable that behavior.</p>
<h2 id="discard-changes">discard-changes<a href="#discard-changes" class="anchor">#</a></h2>
<p>Defaults to <code>false</code> and can be any of <code>true</code>, <code>false</code> or <code>"stash"</code>. This option
allows you to set the default style of handling dirty updates when in
non-interactive mode. <code>true</code> will always discard changes in vendors, while
<code>"stash"</code> will try to stash and reapply. Use this for CI servers or deploy
scripts if you tend to have modified vendors.</p>
<h2 id="archive-format">archive-format<a href="#archive-format" class="anchor">#</a></h2>
<p>Defaults to <code>tar</code>. Overrides the default format used by the archive command.</p>
<h2 id="archive-dir">archive-dir<a href="#archive-dir" class="anchor">#</a></h2>
<p>Defaults to <code>.</code>. Default destination for archives created by the archive
command.</p>
<p>Example:</p>
<pre><code class="language-javascript">{
    "config": {
        "archive-dir": "/home/user/.composer/repo"
    }
}</code></pre>
<h2 id="htaccess-protect">htaccess-protect<a href="#htaccess-protect" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. If set to <code>false</code>, Composer will not create <code>.htaccess</code> files
in the Composer home, cache, and data directories.</p>
<h2 id="lock">lock<a href="#lock" class="anchor">#</a></h2>
<p>Defaults to <code>true</code>. If set to <code>false</code>, Composer will not create a <code>composer.lock</code>
file and will ignore it if one is present.</p>
<h2 id="platform-check">platform-check<a href="#platform-check" class="anchor">#</a></h2>
<p>Defaults to <code>php-only</code> which only checks the PHP version. Set to <code>true</code> to also
check the presence of extension. If set to <code>false</code>, Composer will not create and
require a <code>platform_check.php</code> file as part of the autoloader bootstrap.</p>
<h2 id="secure-svn-domains">secure-svn-domains<a href="#secure-svn-domains" class="anchor">#</a></h2>
<p>Defaults to <code>[]</code>. Lists domains which should be trusted/marked as using a secure
Subversion/SVN transport. By default svn:// protocol is seen as insecure and will
throw, but you can set this config option to <code>["example.org"]</code> to allow using svn
URLs on that hostname. This is a better/safer alternative to disabling <code>secure-http</code>
altogether.</p>
<h2 id="bump-after-update">bump-after-update<a href="#bump-after-update" class="anchor">#</a></h2>
<p>Defaults to <code>false</code> and can be any of <code>true</code>, <code>false</code>, <code>"dev"</code> or <code>"no-dev"</code>. If
set to true, Composer will run the <code>bump</code> command after running the <code>update</code> command.
If set to <code>"dev"</code> or <code>"no-dev"</code> then only the corresponding dependencies will be bumped.</p>
<h2 id="allow-missing-requirements">allow-missing-requirements<a href="#allow-missing-requirements" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. Ignores error during <code>install</code> if there are any missing
requirements - the lock file is not up to date with the latest changes in
<code>composer.json</code>.</p>
<h2 id="update-with-minimal-changes">update-with-minimal-changes<a href="#update-with-minimal-changes" class="anchor">#</a></h2>
<p>Defaults to <code>false</code>. If set to true, Composer will only perform absolutely necessary
changes to transitive dependencies during update.
Can also be set via the <code>COMPOSER_MINIMAL_CHANGES=1</code> env var.</p>
<p class="prev-next">&larr; <a href="05-repositories.md">Repositories</a>  |  <a href="07-runtime.md">Runtime</a> &rarr;</p>

    <p class="fork-and-edit">
        Found a typo? Something is wrong in this documentation?
        <a href="https://github.com/composer/composer/edit/main/doc/06-config.md"
           title="Go to the docs to fork and propose updates (external link)"
           aria-label="Go to the docs to fork and propose updates (external link)">Fork and edit</a> it!
    </p>
                </div>
            </main>
            <footer>
                                
                <p class="license">
                    Composer and all content on this site are released under the <a href="https://github.com/composer/composer/blob/main/LICENSE" title="View the MIT license (external link to GitHub.com)" aria-label="View the MIT license (external link to GitHub.com)">MIT license</a>.
                </p>
            </footer>
        </div>

        <script src="/build/app.js?v=3"></script>
    </body>
</html>
