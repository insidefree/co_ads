set :application, "wix-adsense"
set :domain,      "wix.codeoasis.com"
set :user,        "root"
set :deploy_to,   "/var/www/wix-adsense"
set :app_path,    "app"
set :web_path,    "web"

# set permissions
set :writable_dirs,       ["app/cache", "app/logs"]
set :webserver_user,      "www-data"
set :permission_method,   :chown
set :use_set_permissions, true

set :repository,  "git@git.codeoasis.com:wix/wix-adsense.git"
set :scm,         :git
set :deploy_via,  :remote_cache

set :model_manager, "doctrine"
# Or: `propel`

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set :use_composer,    true
set :copy_vendors, true

set  :keep_releases,  5

# Confirmations will not be requested from the command line.
set :interactive_mode, false
 
# The following line tells Capifony to deploy the last Git tag.
# set :branch, `git tag`.split("\n").last
set :branch, "jenkins"



task :provide_permissions do
  run "cd #{release_path}; chown -R www-data:www-data .; chmod -R 777 app/cache app/logs"

  capifony_puts_ok
end

task :restart_php do
  run "cd #{release_path}; service php-fpm restart"

  capifony_puts_ok
end

after "grunt", "provide_permissions"
after "provide_permissions", "restart_php"

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL
