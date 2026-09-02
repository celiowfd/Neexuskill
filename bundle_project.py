import os

def bundle_project():
    output_file = 'PROJETO_COMPLETO_PARA_GPT.md'
    directories_to_scan = [
        'plugins/pdsm-plugin',
        'plugins/pdsm-client'
    ]
    
    with open(output_file, 'w', encoding='utf-8') as outfile:
        outfile.write("# 📦 Pack de Sites Manager v2.1 (Security-Gated)\n\n")
        outfile.write("Este documento contém o código-fonte integral e a documentação do projeto, estruturado para análise de IA.\n\n")
        
        for directory in directories_to_scan:
            if not os.path.exists(directory):
                continue
                
            outfile.write(f"## Diretório: `{directory}`\n\n")
            
            for root, dirs, files in os.walk(directory):
                for file in files:
                    # Ignore .zip, .png, etc
                    if file.endswith(('.zip', '.png', '.jpg', '.jpeg', '.pdf')):
                        continue
                        
                    file_path = os.path.join(root, file)
                    relative_path = os.path.relpath(file_path, start='.')
                    
                    outfile.write(f"### Arquivo: `{relative_path}`\n\n")
                    
                    # Determine markdown language
                    ext = file.split('.')[-1]
                    lang = ext if ext in ['php', 'js', 'css', 'json', 'xml', 'md', 'html'] else 'text'
                    
                    try:
                        with open(file_path, 'r', encoding='utf-8') as infile:
                            content = infile.read()
                            outfile.write(f"```{lang}\n{content}\n```\n\n")
                    except Exception as e:
                        outfile.write(f"> Erro ao ler arquivo: {str(e)}\n\n")

if __name__ == '__main__':
    bundle_project()
    print("Bundle gerado com sucesso!")
