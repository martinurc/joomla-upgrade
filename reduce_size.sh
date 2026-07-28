#!/usr/bin/env bash
# ==============================================================================
# ACAVe Media File Size Reduction Script
# Reduces file size in KB for JPG, PNG, and PDF files without altering
# image resolutions/dimensions or document page structures.
# ==============================================================================

set -e

TARGET_DIR="${1:-images}"

if [ ! -d "$TARGET_DIR" ]; then
    echo "Error: Directory '$TARGET_DIR' does not exist."
    echo "Usage: ./reduce_size.sh [directory_path]"
    echo "Example: ./reduce_size.sh images"
    exit 1
fi

echo "================================================================="
echo " Starting Media Size Optimization"
echo " Target Directory : $TARGET_DIR"
echo "================================================================="

INITIAL_SIZE_KB=$(du -sk "$TARGET_DIR" | cut -f1)
echo "Initial Directory Size: $((INITIAL_SIZE_KB / 1024)) MB ($INITIAL_SIZE_KB KB)"
echo "-----------------------------------------------------------------"

# Python optimization script for JPG and PNG
python3 - "$TARGET_DIR" << 'EOF'
import os
import sys
import subprocess
from PIL import Image

target_dir = sys.argv[1]

jpg_count = 0
png_count = 0
pdf_count = 0
bytes_saved = 0

print("Processing JPG, PNG, and PDF files...")

for root, dirs, files in os.walk(target_dir):
    for filename in files:
        filepath = os.path.join(root, filename)
        ext = filename.split('.')[-1].lower() if '.' in filename else ''
        
        # Optimize JPG / JPEG
        if ext in ('jpg', 'jpeg'):
            try:
                orig_size = os.path.getsize(filepath)
                if orig_size < 10240: # Skip small files (< 10KB)
                    continue
                im = Image.open(filepath)
                # Keep original dimensions, convert CMYK if needed
                if im.mode in ("RGBA", "P"):
                    im = im.convert("RGB")
                temp_path = filepath + ".tmp_opt.jpg"
                im.save(temp_path, 'JPEG', quality=82, optimize=True)
                new_size = os.path.getsize(temp_path)
                if new_size < orig_size:
                    os.replace(temp_path, filepath)
                    bytes_saved += (orig_size - new_size)
                    jpg_count += 1
                else:
                    os.remove(temp_path)
            except Exception as e:
                if os.path.exists(filepath + ".tmp_opt.jpg"):
                    os.remove(filepath + ".tmp_opt.jpg")

        # Optimize PNG
        elif ext == 'png':
            try:
                orig_size = os.path.getsize(filepath)
                if orig_size < 10240:
                    continue
                im = Image.open(filepath)
                temp_path = filepath + ".tmp_opt.png"
                im.save(temp_path, 'PNG', optimize=True)
                new_size = os.path.getsize(temp_path)
                if new_size < orig_size:
                    os.replace(temp_path, filepath)
                    bytes_saved += (orig_size - new_size)
                    png_count += 1
                else:
                    os.remove(temp_path)
            except Exception as e:
                if os.path.exists(filepath + ".tmp_opt.png"):
                    os.remove(filepath + ".tmp_opt.png")

        # Optimize PDF using Ghostscript if available
        elif ext == 'pdf':
            try:
                orig_size = os.path.getsize(filepath)
                if orig_size < 50000: # Skip small PDFs (< 50KB)
                    continue
                temp_path = filepath + ".tmp_opt.pdf"
                cmd = [
                    'gs', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4',
                    '-dPDFSETTINGS=/ebook', '-dNOPAUSE', '-dQUIET', '-dBATCH',
                    f'-sOutputFile={temp_path}', filepath
                ]
                res = subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
                if res.returncode == 0 and os.path.exists(temp_path):
                    new_size = os.path.getsize(temp_path)
                    if new_size > 0 and new_size < orig_size:
                        os.replace(temp_path, filepath)
                        bytes_saved += (orig_size - new_size)
                        pdf_count += 1
                    else:
                        os.remove(temp_path)
            except Exception as e:
                if os.path.exists(filepath + ".tmp_opt.pdf"):
                    os.remove(filepath + ".tmp_opt.pdf")

print(f"Done.")
print(f"  Optimized JPG files : {jpg_count}")
print(f"  Optimized PNG files : {png_count}")
print(f"  Optimized PDF files : {pdf_count}")
print(f"  Total Bytes Saved   : {bytes_saved / (1024*1024):.2f} MB")
EOF

FINAL_SIZE_KB=$(du -sk "$TARGET_DIR" | cut -f1)
SAVED_KB=$((INITIAL_SIZE_KB - FINAL_SIZE_KB))

echo "-----------------------------------------------------------------"
echo "Final Directory Size   : $((FINAL_SIZE_KB / 1024)) MB ($FINAL_SIZE_KB KB)"
echo "Total Space Saved      : $((SAVED_KB / 1024)) MB ($SAVED_KB KB)"
echo "================================================================="
