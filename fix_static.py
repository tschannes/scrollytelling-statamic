import os
import json
import shutil
import re

# Paths
BASE_DIR = os.getcwd()
PUBLIC_DIR = os.path.join(BASE_DIR, 'public')
STATIC_DIR = os.path.join(BASE_DIR, 'static')
MANIFEST_PATH = os.path.join(PUBLIC_DIR, 'build', 'manifest.json')

def fix_html_files():
    if not os.path.exists(MANIFEST_PATH):
        print("Manifest not found!")
        return

    with open(MANIFEST_PATH, 'r') as f:
        manifest = json.load(f)

    # Prepare tags to inject
    tags = []
    
    # We want to maintain some order or just inject all entries
    for key, value in manifest.items():
        if value.get('isEntry'):
            file_path = f"/build/{value['file']}"
            if file_path.endswith('.css'):
                tags.append(f'<link rel="stylesheet" href="{file_path}">')
            elif file_path.endswith('.js'):
                tags.append(f'<script type="module" src="{file_path}"></script>')

    injection_code = "\n\t".join(tags)
    print(f"Injecting into HTML files:\n{injection_code}")

    for root, dirs, files in os.walk(STATIC_DIR):
        for file in files:
            if file.endswith('.html'):
                file_full_path = os.path.join(root, file)
                with open(file_full_path, 'r') as f:
                    content = f.read()
                
                # Find the spot before </head>
                if '</head>' in content:
                    new_content = content.replace('</head>', f'{injection_code}\n</head>')
                    
                    # Also fix any root-relative paths to be truly relative if we wanted, 
                    # but for now let's just ensure they are correct for root-hosting
                    with open(file_full_path, 'w') as f:
                        f.write(new_content)
                    print(f"Fixed {file_full_path}")

def copy_missing_assets():
    # Copy assets
    src_assets = os.path.join(PUBLIC_DIR, 'assets')
    dst_assets = os.path.join(STATIC_DIR, 'assets')
    if os.path.exists(src_assets):
        if os.path.exists(dst_assets):
            shutil.rmtree(dst_assets)
        shutil.copytree(src_assets, dst_assets)
        print(f"Copied assets to {dst_assets}")

    # Copy favicon
    src_fav = os.path.join(PUBLIC_DIR, 'favicon.ico')
    dst_fav = os.path.join(STATIC_DIR, 'favicon.ico')
    if os.path.exists(src_fav):
        shutil.copy2(src_fav, dst_fav)
        print(f"Copied favicon.ico")

if __name__ == "__main__":
    copy_missing_assets()
    fix_html_files()
