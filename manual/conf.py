# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'Zaakvolgsysteem Handleiding'

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = []

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store', 'venv']

locale_dirs = ['_locales/']
language = 'nl'

# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_title = "Zaakvolgsysteem Handleiding"
html_short_title = "ZVS Handleiding"

html_theme = "sphinx_icore_open"
html_theme_path = ["_themes"]

html_static_path = ['_static']
html_baseurl = '/handleiding/html/'

html_theme_options = {
		"logo": "_static/img/logo.svg",
}
