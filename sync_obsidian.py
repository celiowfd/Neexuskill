import os
import shutil
from pathlib import Path

def sync_docs_to_obsidian():
    # Diretórios de Origem e Destino
    source_dir = Path("a:/GITHUB/Neexuskill")
    obsidian_vault = Path("B:/Google Drive/Organizações CWFD/Organizaoes CWFD/CellSolucoes")
    target_dir = obsidian_vault / "PDSM-Docs-Archived"
    
    # Cria o diretório no Obsidian se não existir
    target_dir.mkdir(parents=True, exist_ok=True)
    
    # Extensões que queremos copiar (Markdown, PDF, txt)
    allowed_extensions = {".md", ".pdf", ".txt"}
    
    # Arquivos ou pastas a ignorar
    ignore_paths = {".git", "node_modules", "venv", "__pycache__"}
    
    copied_count = 0
    print(f"Iniciando sincronização para: {target_dir}")
    
    for root, dirs, files in os.walk(source_dir):
        # Filtra pastas ignoradas
        dirs[:] = [d for d in dirs if d not in ignore_paths]
        
        for file in files:
            file_path = Path(root) / file
            
            if file_path.suffix.lower() in allowed_extensions:
                # Mantém a estrutura de pastas original (relativa à raiz do projeto)
                relative_path = file_path.relative_to(source_dir)
                dest_path = target_dir / relative_path
                
                # Cria a subpasta no destino, se necessário
                dest_path.parent.mkdir(parents=True, exist_ok=True)
                
                # Copia o arquivo
                shutil.copy2(file_path, dest_path)
                copied_count += 1
                print(f"Copiado: {relative_path}")
                
    print(f"\n✅ Sincronização concluída! {copied_count} documentos enviados para o Obsidian.")

if __name__ == "__main__":
    sync_docs_to_obsidian()
