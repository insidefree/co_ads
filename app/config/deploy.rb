set :stages,        %w(production stage)
set :default_stage, "staging"
set :stage_dir,     "app/config"
require 'capistrano/ext/multistage'
